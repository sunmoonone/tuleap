<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

require_once TRACKER_BASE_DIR . '/../tests/bootstrap.php';

class Tracker_Artifact_MailGatewayRecipientFactoryTest extends TuleapTestCase {

    public function setUp() {
        parent::setUp();
        $this->user     = aUser()->withId(123)->build();
        $this->artifact = anArtifact()->withId(101)->build();
        $this->salt     = 'whatever';
        $this->host     = 'tuleap.example.com';

        $this->artifact_factory = stub('Tracker_ArtifactFactory')->getArtifactById(101)->returns($this->artifact);
        $this->user_manager     = stub('UserManager')->getUserById(123)->returns($this->user);

        $this->factory = new Tracker_Artifact_MailGatewayRecipientFactory(
            $this->artifact_factory,
            $this->user_manager,
            $this->salt,
            $this->host
        );
    }

    public function itGeneratesAMailGatewayRecipientFromEmail() {
        $email = '101-5a2a341193b34695885091bbf5f75d68-123@tuleap.example.com';
        $recipient = $this->factory->getFromEmail($email);

        $this->assertEqual($recipient->getArtifact(), $this->artifact);
        $this->assertEqual($recipient->getUser(), $this->user);
        $this->assertEqual($recipient->getEmail(), $email);
    }

    public function itThrowsAnAxceptionWhenArtifactDoesNotExist() {
        $email = '000000-5a2a341193b34695885091bbf5f75d68-123@tuleap.example.com';
        $this->expectException('Tracker_Artifact_MailGatewayRecipientArtifactDoesNotExistException');
        $this->factory->getFromEmail($email);
    }

    public function itThrowsAnAxceptionWhenUserDoesNotExist() {
        $email = '101-5a2a341193b34695885091bbf5f75d68-00000@tuleap.example.com';
        $this->expectException('Tracker_Artifact_MailGatewayRecipientUserDoesNotExistException');
        $this->factory->getFromEmail($email);
    }

    public function itThrowsAnAxceptionWhenHashIsInvalid() {
        $email = '101-invalidhash-123@tuleap.example.com';
        $this->expectException('Tracker_Artifact_MailGatewayRecipientInvalidHashException');
        $this->factory->getFromEmail($email);
    }

    public function itGeneratesAMailGatewayRecipientFromUserAndArtifact() {
        $email = '101-5a2a341193b34695885091bbf5f75d68-123@tuleap.example.com';
        $recipient = $this->factory->getFromUserAndArtifact($this->user, $this->artifact);

        $this->assertEqual($recipient->getArtifact(), $this->artifact);
        $this->assertEqual($recipient->getUser(), $this->user);
        $this->assertEqual($recipient->getEmail(), $email);

    }
}

?>