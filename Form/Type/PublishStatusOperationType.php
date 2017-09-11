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

namespace CampaignChain\Operation\FacebookBundle\Form\Type;

use CampaignChain\CoreBundle\Form\Type\OperationType;
use CampaignChain\Operation\FacebookBundle\Entity\UserStatus;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PublishStatusOperationType extends OperationType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->setOptions($options);

        $builder
            ->add('message', 'textarea', array(
                'label' => false,
                'attr' => array(
                    'placeholder' => 'Compose message...',
                    'maxlength' => 2000,
                ),
            ));

        if($options['data'] instanceof UserStatus){
            $builder
                ->add('privacy', ChoiceType::class, array(
                    'label' => 'Audience',
                    'choices'   => array(
                        'Public' => 'EVERYONE',
                        'Friends' => 'ALL_FRIENDS',
                        'Friends of Friends' => 'FRIENDS_OF_FRIENDS',
                        'Only Me' => 'SELF',
                    ),
                    'multiple'  => false,
                ));
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $defaults = array(
            'data_class' => 'CampaignChain\Operation\FacebookBundle\Entity\StatusBase',
        );

        $resolver->setDefaults($defaults);
    }

    public function getBlockPrefix()
    {
        return 'campaignchain_operation_facebook_publish_status';
    }
}