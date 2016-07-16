<?php
/*
 * Copyright 2016 CampaignChain, Inc. <info@campaignchain.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *    http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace CampaignChain\Operation\FacebookBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class UserStatus extends StatusBase
{
    const PRIVACY_EVERYONE = 'EVERYONE';
    const PRIVACY_ALL_FRIENDS = 'ALL_FRIENDS';
    const PRIVACY_FRIENDS_OF_FRIENDS = 'FRIENDS_OF_FRIENDS';
    const PRIVACY_SELF = 'SELF';

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    protected $privacy;

    /**
     * Set privacy
     *
     * @param string $privacy
     * @return UserStatus
     */
    public function setPrivacy($privacy)
    {
        if (!in_array($privacy, array(
            self::PRIVACY_ALL_FRIENDS,
            self::PRIVACY_EVERYONE,
            self::PRIVACY_FRIENDS_OF_FRIENDS,
            self::PRIVACY_SELF
        ))) {
            throw new \InvalidArgumentException("Invalid Facebook status privacy.");
        }
        $this->privacy = $privacy;

        return $this;
    }

    /**
     * Get privacy
     *
     * @return string
     */
    public function getPrivacy()
    {
        return $this->privacy;
    }
}
