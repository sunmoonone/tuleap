<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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
 
/**
 * Manage values in changeset for fields
 * @abstract
 */
abstract class Tracker_Artifact_ChangesetValue {
    
    
    /**
     * @var int
     */
    protected $id;
    
    /**
     * @var Tracker_FormElement_Field
     */
    protected $field;
    
    /**
     * @var boolean
     */
    protected $has_changed;
    
    /**
     * Constructor
     *
     * @param int                       $id          The id of the changeset value
     * @param Tracker_FormElement_Field $field       The field of the value
     * @param boolean                   $has_changed If the changeset value has chnged from the previous one
     */
    public function __construct($id, $field, $has_changed) {
        $this->id          = $id;
        $this->field       = $field;
        $this->has_changed = $has_changed;
    }

    /**
     * Get the id of the value
     *
     * @return int
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Get the field of the value
     *
     * @return Tracker_FormElement_Field
     */
    public function getField() {
        return $this->field;
    }
    
    /**
     * Returns true if the changeset value has changed, false otherwise
     *
     * @return boolean true if the changeset value has changed, false otherwise
     */
    public function hasChanged() {
        return $this->has_changed;
    }
    
    /**
     * Returns a diff between current changeset value and changeset value in param
     *
     * @param Tracker_Artifact_ChangesetValue $changeset_value The changeset value to compare to this changeset value
     * @param string                          $format          The format of the diff (html, text, ...)
     * @param PFUser                          $user            The user or null
     *
     * @return string The difference between another $changeset_value, false if no differences
     */
    public abstract function diff($changeset_value, $format = 'html', PFUser $user = null);

    /**
     * Returns a mail format diff between current changeset value and changeset value in param
     *
     * @param Tracker_Artifact_ChangesetValue $changeset_value The changeset value to compare to this changeset value
     * @param string                          $format          The format of the diff (html, text, ...)
     * @param PFUser                          $user            The user or null
     *
     * @return string The difference between another $changeset_value, false if no differences
     */
    public function mailDiff($changeset_value, $format = 'html', PFUser $user = null, $artifact_id, $changeset_id) {
        return $this->diff($changeset_value, $format, $user);
    }

    /**
     * Returns a modal format diff between current changeset value and changeset value in param
     *
     * @param Tracker_Artifact_ChangesetValue $changeset_value The changeset value to compare to this changeset value
     * @param string                          $format          The format of the diff (html, text, ...)
     * @param PFUser                          $user            The user or null
     *
     * @return string The difference between another $changeset_value, false if no differences
     */
    public function modalDiff($changeset_value, $format = 'html', PFUser $user = null) {
        return $this->diff($changeset_value, $format, $user);
    }
    
    /**
     * Returns the SOAP value of this changeset value
     *
     * @param PFUser $user
     *
     * @return string The value of this artifact changeset value for Soap API
     */
    public abstract function getSoapValue(PFUser $user);

    /**
     * Return the REST value of this changeset value
     *
     * @param PFUser $user
     *
     * @return Tuleap\Tracker\REST\Artifact\ArtifactFieldValueRepresentation
     */
    public abstract function getRESTValue(PFUser $user);

    /**
     * Return the full REST value of this changeset value
     *
     * @param PFUser $user
     *
     * @return Tuleap\Tracker\REST\Artifact\ArtifactFieldValueRepresentation
     */
    public abstract function getFullRESTValue(PFUser $user);

    /**
     * @return mixed
     */
    public abstract function accept(Tracker_Artifact_ChangesetValueVisitor $visitor);

    /**
     * Returns the Json value of this changeset value
     *
     * @return string The value of this artifact changeset value for Json format
     */
    public function getJsonValue() {
        return $this->getValue();
    }

    /**
     * Returns the value of this changeset value
     *
     * @return string The value of this artifact changeset value
     */
    public abstract function getValue();

    /**
     * By default, changeset values are returned as string in 'value' field
     */
    protected function encapsulateRawSoapValue($value) {
        return array('value' => (string)$value);
    }

    protected function getRESTRepresentation($value) {
        $classname_with_namespace = 'Tuleap\Tracker\REST\Artifact\ArtifactFieldValueRepresentation';

        $artifact_field_value_representation = new $classname_with_namespace;
        $artifact_field_value_representation->build(
            $this->field->getId(),
            $this->field->getLabel(),
            $value
        );

        return $artifact_field_value_representation;
    }

    protected function getFullRESTRepresentation($value) {
        $classname_with_namespace = 'Tuleap\Tracker\REST\Artifact\ArtifactFieldValueFullRepresentation';

        $artifact_field_value_full_representation = new $classname_with_namespace;
        $artifact_field_value_full_representation->build(
            $this->field->getId(),
            Tracker_FormElementFactory::instance()->getType($this->field),
            $this->field->getLabel(),
            $value
        );

        return $artifact_field_value_full_representation;
    }
}
