<?php
/**
 * Copyright (c) Enalean, 2011. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */
require_once 'Git_GitoliteTestCase.class.php';

class Git_GitoliteDriverTest extends Git_GitoliteTestCase {

    /** @var Git_Gitolite_GitoliteRCReader */
    private $gitoliterc_reader;

    /** @var Git_Gitolite_ConfigPermissionsSerializer */
    private $another_gitolite_permissions_serializer;

    /** @var Git_GitoliteDriver */
    private $a_gitolite_driver;

    /** @var Git_GitoliteDriver */
    private $another_gitolite_driver;

    /** @var Git_Gitolite_GitoliteConfWriter */
    private $gitolite_conf_writer;

    /** @var Git */
    private $another_git_exec;

    /** @var Git_Gitolite_ProjectSerializer */
    private $a_gitolite_project_serializer;

    /** @var ProjectManager */
    private $project_manager;

    public function setUp() {
        parent::setUp();

        $this->project_manager   = mock('ProjectManager');
        $this->gitoliterc_reader = mock('Git_Gitolite_GitoliteRCReader');

        $this->another_gitolite_permissions_serializer = new Git_Gitolite_ConfigPermissionsSerializer(
            $this->mirror_data_mapper,
            'whatever'
        );

        $this->a_gitolite_project_serializer = new Git_Gitolite_ProjectSerializer(
            $this->logger,
            $this->repository_factory,
            $this->another_gitolite_permissions_serializer,
            $this->url_manager
        );

        $this->gitolite_conf_writer = new Git_Gitolite_GitoliteConfWriter(
            $this->another_gitolite_permissions_serializer,
            $this->a_gitolite_project_serializer,
            $this->gitoliterc_reader,
            $this->mirror_data_mapper,
            mock('Logger'),
            $this->project_manager,
            $this->sys_data_dir . '/gitolite/admin'
        );

        $this->a_gitolite_driver = new Git_GitoliteDriver(
            $this->logger,
            $this->git_system_event_manager,
            $this->url_manager,
            $this->gitExec,
            $this->repository_factory,
            $this->another_gitolite_permissions_serializer,
            $this->gitolite_conf_writer,
            $this->project_manager,
            $this->mirror_data_mapper
        );

        $this->another_git_exec = mock('Git_Exec');
        stub($this->another_git_exec)->add()->returns(true);

        $this->another_gitolite_driver = new Git_GitoliteDriver(
            $this->logger,
            $this->git_system_event_manager,
            $this->url_manager,
            $this->another_git_exec,
            $this->repository_factory,
            $this->another_gitolite_permissions_serializer,
            $this->gitolite_conf_writer,
            $this->project_manager,
            $this->mirror_data_mapper
        );
    }

    public function tearDown() {
        parent::tearDown();

        unset($GLOBALS['sys_data_dir']);
    }

    public function testGitoliteConfUpdate() {
        stub($this->gitoliterc_reader)->getHostname()->returns(null);

        touch($this->_glAdmDir.'/conf/projects/project1.conf');

        $this->another_gitolite_driver->updateMainConfIncludes();

        $gitoliteConf = $this->getGitoliteConf();

        $this->assertPattern('#^include "projects/project1.conf"$#m', $gitoliteConf);
    }

    protected function getGitoliteConf() {
        return file_get_contents($this->_glAdmDir.'/conf/gitolite.conf');
    }

    protected function getFileConf($filename) {
        return file_get_contents($this->_glAdmDir.'/conf/'.$filename.'.conf');
    }

    public function itCanRenameProject() {
        $new_name = 'newone';
        stub($this->project_manager)->getProjectByUnixName($new_name)->returns(aMockProject()->withUnixName($new_name)->build());
        $this->gitExec->expectOnce('push');

        $this->assertTrue(is_file($this->_glAdmDir.'/conf/projects/legacy.conf'));
        $this->assertFalse(is_file($this->_glAdmDir.'/conf/projects/newone.conf'));

        $this->assertTrue($this->a_gitolite_driver->renameProject('legacy', $new_name));

        clearstatcache(true, $this->_glAdmDir.'/conf/projects/legacy.conf');
        $this->assertFalse(is_file($this->_glAdmDir.'/conf/projects/legacy.conf'));
        $this->assertTrue(is_file($this->_glAdmDir.'/conf/projects/newone.conf'));
        $this->assertIdentical(
            file_get_contents($this->_fixDir.'/perms/newone.conf'),
            file_get_contents($this->_glAdmDir.'/conf/projects/newone.conf')
        );
        $this->assertNoPattern('`\ninclude "projects/legacy.conf"\n`', $this->getGitoliteConf());
        $this->assertPattern('`\ninclude "projects/newone.conf"\n`', $this->getGitoliteConf());
        $this->assertEmptyGitStatus();
    }

    public function itLogsEverytimeItPushes() {
        expect($this->logger)->debug()->count(2);

        $this->driver->push();
    }

    public function itOnlyIncludeHOSTNAMERelatedConfFileIfHOSTNAMEVariableIsSetInGitoliteRcFile() {
        stub($this->gitoliterc_reader)->getHostname()->returns("master");

        touch($this->_glAdmDir.'/conf/projects/project1.conf');

        $this->another_gitolite_driver->updateMainConfIncludes();

        $gitoliteConf = $this->getGitoliteConf();

        $this->assertPattern('#^include "%HOSTNAME.conf"$#m', $gitoliteConf);
        $this->assertNoPattern('#^include "projects/project1.conf"$#m', $gitoliteConf);
    }

    public function itWritesTheGitoliteConfFileInTheHOSTNAMEDotConfFileIfHostnameVariableIsSet()
    {
        $hostname = "master";
        stub($this->gitoliterc_reader)->getHostname()->returns($hostname);

        touch($this->_glAdmDir . '/conf/projects/project1.conf');

        $this->another_gitolite_driver->updateMainConfIncludes();

        $gitoliteConf = $this->getFileConf($hostname);
        $this->assertPattern('#^include "projects/project1.conf"$#m', $gitoliteConf);
    }

    public function itAddsAllTheRequiredFilesForPush()
    {
        $hostname = "master";

        stub($this->gitoliterc_reader)->getHostname()->returns($hostname);

        touch($this->_glAdmDir . '/conf/projects/project1.conf');

        expect($this->another_git_exec)->add()->count(2);
        expect($this->another_git_exec)->add('conf/gitolite.conf')->at(0);
        expect($this->another_git_exec)->add('conf/master.conf')->at(1);

        $this->another_gitolite_driver->updateMainConfIncludes();
    }
}

class Git_GitoliteDriver_ForkTest extends Git_GitoliteTestCase {

    protected function _getFileGroupName($filePath) {
        clearstatcache();
        $rootStats = stat($filePath);
        $groupInfo = posix_getgrgid($rootStats[5]);
        return $groupInfo['name'];
    }
    
    protected function assertNameSpaceFileHasBeenInitialized($repoPath, $namespace, $group) {
        $namespaceInfoFile = $repoPath.'/tuleap_namespace';
        $this->assertTrue(file_exists($namespaceInfoFile), 'the file (' . $namespaceInfoFile . ') does not exists');
        $this->assertEqual(file_get_contents($namespaceInfoFile), $namespace);
        $this->assertEqual($group, $this->_getFileGroupName($namespaceInfoFile));

    }
    
    protected function assertWritableByGroup($new_root_dir, $group) {
        $this->assertEqual($group, $this->_getFileGroupName($new_root_dir));
        $this->assertEqual($group, $this->_getFileGroupName($new_root_dir .'/hooks/gitolite_hook.sh'));

        clearstatcache();
        $rootStats = stat($new_root_dir);
        $this->assertPattern('/.*770$/', decoct($rootStats[2]));
    }
    
    public function assertRepoIsClonedWithHooks($new_root_dir) {
        $this->assertTrue(is_dir($new_root_dir), "the new git repo dir ($new_root_dir) wasn't found.");
        $new_repo_HEAD = $new_root_dir . '/HEAD';
        $this->assertTrue(file_exists($new_repo_HEAD), 'the file (' . $new_repo_HEAD . ') does not exists');
        $this->assertTrue(file_exists($new_root_dir . '/hooks/gitolite_hook.sh'), 'the hook file wasn\'t copied to the fork');
    }

    public function testFork_CloneEmptyToSpecifiedPath() {

        if (posix_getgrnam('gitolite') == false) {
            echo "testFork_CloneEmptyToSpecifiedPath: Cannot test 'cause there is no 'gitolite' user on server (CI)";
        } else {
            $name = 'tulip';
            $new_ns = 'repos/new/repo/';
            $old_ns = 'repos/';
            $old_root_dir = $this->repoDir .'/'. $old_ns . $name .'.git';
            $new_root_dir = $this->repoDir .'/'. $new_ns . $name .'.git';

            mkdir($old_root_dir, 0770, true);
            exec('GIT_DIR='. $old_root_dir .' git init --bare --shared=group');
            exec('cd '.$old_root_dir.' && touch hooks/gitolite_hook.sh');

            $this->assertTrue($this->driver->fork($name, $old_ns, $new_ns));
            $this->assertRepoIsClonedWithHooks($new_root_dir);

            $this->assertWritableByGroup($new_root_dir, 'gitolite');
            $this->assertNameSpaceFileHasBeenInitialized($new_root_dir, $new_ns, 'gitolite');
        }

    }
    
    public function testForkShouldNotCloneOnExistingRepositories() {
        $name = 'tulip';
        $new_ns = 'repos/new/repo/';
        $old_ns = 'repos/';
        $old_root_dir = $this->repoDir .'/'. $old_ns . $name .'.git';
        $new_root_dir = $this->repoDir .'/'. $new_ns . $name .'.git';
        
        mkdir($old_root_dir, 0770, true);
        exec('GIT_DIR='. $old_root_dir .' git --bare init --shared=group');
        
        mkdir($new_root_dir, 0770, true);
        exec('GIT_DIR='. $new_root_dir .' git --bare init --shared=group');
        
        $this->assertFalse($this->driver->fork($name, $old_ns, $new_ns));
    }
    
    
    // JM: Dont understant this test, should it be in _Fork or the miscallaneous part?
    public function itIsInitializedEvenIfThereIsNoMaster() {
        $this->assertTrue($this->driver->isInitialized($this->_fixDir.'/headless.git'));
    }
    
    public function itIsNotInitializedldIfThereIsNoValidDirectory() {
        $this->assertFalse($this->driver->isInitialized($this->_fixDir));
    }
}
