<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) CampaignChain Inc. <info@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CampaignChain\Operation\FacebookBundle\Entity;

use CampaignChain\CoreBundle\Entity\Meta;
use CampaignChain\Location\FacebookBundle\Entity\LocationBase;
use Doctrine\ORM\Mapping as ORM;
use CampaignChain\CoreBundle\Util\ParserUtil;

/**
 * @ORM\Entity
 * @ORM\Table(name="campaignchain_operation_facebook_status")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\DiscriminatorMap( { "user" = "UserStatus", "page" = "PageStatus" } )
 */
abstract class StatusBase extends Meta
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\OneToOne(targetEntity="CampaignChain\CoreBundle\Entity\Operation", cascade={"persist"})
     */
    protected $operation;

    /**
     * @ORM\ManyToOne(targetEntity="CampaignChain\Location\FacebookBundle\Entity\LocationBase", inversedBy="statuses")
     * @ORM\JoinColumn(referencedColumnName="id")
     */
    protected $facebookLocation;

    /**
     * @ORM\Column(type="text")
     */
    protected $message;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $postId;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $url;

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
     * Set message
     *
     * @param string $message
     * @return Status
     */
    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Get message
     *
     * @return string 
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Set postId
     *
     * @param string $postId
     * @return Status
     */
    public function setPostId($postId)
    {
        $this->postId = $postId;

        return $this;
    }

    /**
     * Get postId
     *
     * @return string 
     */
    public function getPostId()
    {
        return $this->postId;
    }

    /**
     * Set url
     *
     * @param string $url
     * @return Status
     */
    public function setUrl($url)
    {
        $this->url = ParserUtil::sanitizeUrl($url);

        return $this;
    }

    /**
     * Get url
     *
     * @return string 
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set operation
     *
     * @param \CampaignChain\CoreBundle\Entity\Operation $operation
     * @return Status
     */
    public function setOperation(\CampaignChain\CoreBundle\Entity\Operation $operation = null)
    {
        $this->operation = $operation;

        return $this;
    }

    /**
     * Get operation
     *
     * @return \CampaignChain\CoreBundle\Entity\Operation
     */
    public function getOperation()
    {
        return $this->operation;
    }

    /**
     * @param LocationBase $facebookLocation
     * @return $this
     */
    public function setFacebookLocation(LocationBase $facebookLocation = null)
    {
        $this->facebookLocation = $facebookLocation;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getFacebookLocation()
    {
        return $this->facebookLocation;
    }
}
