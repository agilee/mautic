<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Entity\FormEntity;
use Doctrine\Common\Collections\ArrayCollection;
use JMS\Serializer\Annotation as Serializer;

/**
 * Class Lead
 * @ORM\Table(name="leads")
 * @ORM\Entity(repositoryClass="Mautic\LeadBundle\Entity\LeadRepository")
 * @Serializer\ExclusionPolicy("all")
 */
class Lead extends FormEntity
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"full", "limited", "log"})
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Mautic\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="owner_id", referencedColumnName="id", nullable=true)
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"full", "limited", "log"})
     */
    private $owner;

    /**
     * @ORM\Column(type="integer")
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"full", "limited", "log"})
     */
    private $score = 0;

    /**
     * @ORM\OneToMany(targetEntity="LeadFieldValue", mappedBy="lead", cascade={"all"}, orphanRemoval=true, fetch="EXTRA_LAZY")
     */
    private $fields;

    /**
     * @ORM\ManyToMany(targetEntity="Mautic\CoreBundle\Entity\IpAddress", cascade={"merge", "persist", "refresh", "detach"}, fetch="EXTRA_LAZY")
     * @ORM\JoinTable(name="lead_ips_xref",
     *   joinColumns={@ORM\JoinColumn(name="lead_id", referencedColumnName="id")},
     *   inverseJoinColumns={@ORM\JoinColumn(name="ip_id", referencedColumnName="id")}
     * )
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"full", "limited", "log"})
     */
    private $ipAddresses;

    /**
     * Simply used to populate the changeset LeadEvent with updated field values
     *
     * @var
     */
    private $updatedFields = array();

    /**
     * Unmapped array used internally to update field values rather than creating new ones
     *
     * @var
     */
    private $fieldValues = array();

    /**
     * Unmapped array used by the API to return the custom fields and values in a decent format
     *
     * @var array
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"limited"})
     */
    private $customFields = array();


    private $changes;

    private function isChanged($prop, $val)
    {
        if ($prop == 'owner') {
            if ($this->owner && !$val) {
                $this->changes['owner'] = array($this->owner->getName() . ' ('. $this->owner->getId().')', $val);
            } elseif (!$this->owner && $val) {
                $this->changes['owner'] = array($this->owner, $val->getName() . ' ('. $val->getId().')');
            } elseif ($this->owner && $val && $this->owner->getId() != $val->getId()) {
                $this->changes['owner'] = array($this->owner->getName() . '('. $this->owner->getId().')',
                    $val->getName() . '('. $val->getId().')');
            }
        } elseif ($prop == 'ipAddresses') {
            $this->changes['ipAddresses'] = array('', $val->getIpAddress());
        } elseif ($prop == 'fields') {
            $this->changes['fields'][$val[0]] = $val[1];
        } elseif ($this->$prop != $val) {
            $this->changes[$prop] = array($this->$prop, $val);
        }
    }

    public function getChanges()
    {
        return $this->changes;
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->fields = new ArrayCollection();
        $this->ipAddresses = new ArrayCollection();
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set owner
     *
     * @param \Mautic\UserBundle\Entity\User $owner
     * @return Lead
     */
    public function setOwner(\Mautic\UserBundle\Entity\User $owner = null)
    {
        $this->isChanged('owner', $owner);
        $this->owner = $owner;

        return $this;
    }

    /**
     * Get owner
     *
     * @return \Mautic\UserBundle\Entity\User
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * Add fields
     *
     * @param \Mautic\LeadBundle\Entity\LeadFieldValue $fields
     * @param $value
     * @return Lead
     */
    public function addField(\Mautic\LeadBundle\Entity\LeadFieldValue $fields)
    {
        if ($changes = $fields->getChanges()) {
            $this->isChanged('fields', array($fields->getField()->getAlias(), $changes));
        }
        $this->fields[] = $fields;

        return $this;
    }

    /**
     * Remove fields
     *
     * @param \Mautic\LeadBundle\Entity\LeadFieldValue $fields
     */
    public function removeField(\Mautic\LeadBundle\Entity\LeadFieldValue $fields)
    {
        $this->fields->removeElement($fields);
    }

    /**
     * Get fields
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * Add ipAddresses
     *
     * @param \Mautic\CoreBundle\Entity\IpAddress $ipAddresses
     * @return Lead
     */
    public function addIpAddress(\Mautic\CoreBundle\Entity\IpAddress $ipAddresses)
    {
        $this->isChanged('ipAddresses', $ipAddresses);
        $this->ipAddresses[] = $ipAddresses;

        return $this;
    }

    /**
     * Remove ipAddresses
     *
     * @param \Mautic\CoreBundle\Entity\IpAddress $ipAddresses
     */
    public function removeIpAddress(\Mautic\CoreBundle\Entity\IpAddress $ipAddresses)
    {
        $this->ipAddresses->removeElement($ipAddresses);
    }

    /**
     * Get ipAddresses
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getIpAddresses()
    {
        return $this->ipAddresses;
    }

    /**
     * Get full name
     *
     * @param bool $lastFirst
     * @return string
     */
    public function getName($lastFirst = false)
    {
        $this->populateIdentifiers();
        $fullName = "";
        if ($lastFirst && !empty($this->firstName) && !empty($this->lastName)) {
            $fullName = $this->lastName . ", " . $this->firstName;
        } elseif (!empty($this->firstName) && !empty($this->lastName)) {
            $fullName = $this->firstName . " " . $this->lastName;
        } elseif (!empty($this->firstName)) {
            $fullName = $this->firstName;
        } elseif (!empty($this->lastName)) {
            $fullName = $this->lastName;
        }

        return $fullName;
    }

    /**
     * Get the primary identifier for the lead
     *
     * @param bool $lastFirst
     * @return string
     */
    public function getPrimaryIdentifier($lastFirst = false)
    {
        $this->populateIdentifiers();
        if ($name = $this->getName($lastFirst)) {
            return $name;
        } elseif (!empty($this->company)) {
            return $this->company;
        } elseif (!empty($this->email)) {
            return $this->email;
        } elseif (count($ips = $this->getIpAddresses())) {
            return $ips[0]->getIpAddress();
        } else {
            return 'mautic.lead.lead.anonymous';
        }
    }

    /**
     * Get the secondary identifier for the lead; mainly company
     *
     * @return string
     */
    public function getSecondaryIdentifier()
    {
        $this->populateIdentifiers();
        if (!empty($this->company)) {
            return $this->company;
        }
    }

    /**
     * Set score
     *
     * @param integer $score
     * @return Lead
     */
    public function setScore($score)
    {
        $this->score = $score;

        return $this;
    }

    /**
     * Get score
     *
     * @return integer
     */
    public function getScore()
    {
        return $this->score;
    }

    /**
     * Get field values
     *
     * @return array
     */
    public function getFieldValues()
    {
        return $this->fieldValues;
    }

    /**
     * Add a custom field for the API
     *
     * @param $name
     * @param $value
     */
    public function addCustomField($name, $value)
    {
        $this->customFields[$name] = $value;
    }

    /**
     * Get custom fields
     *
     * @return array
     */
    public function getCustomFields()
    {
        return $this->customFields;
    }

    private function populateIdentifiers()
    {
        foreach ($this->fields as $field) {
            $alias = $field->getField()->getAlias();
            if ($alias == 'firstname') {
                $this->firstName = $field->getValue();
            } elseif ($alias == 'lastname') {
                $this->lastName = $field->getValue();
            } elseif ($alias == 'company') {
                $this->company = $field->getValue();
            } elseif ($alias == 'email') {
                $this->email = $field->getValue();
            }
        }

        if (!isset($this->firstName))
            $this->firstName = '';
        if (!isset($this->lastName))
            $this->lastName = '';
        if (!isset($this->company))
            $this->company = '';
        if (!isset($this->email))
            $this->email = '';
    }
}