<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use \AppUserBundle\Form\RegistrationType;
use EWZ\Bundle\RecaptchaBundle\Form\Type\EWZRecaptchaType;

class PatientsActType extends AbstractType {

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
                ->add('nameact', null, array('required' => true, 'label' => 'Nombre'))
                ->add('lastnameact', null, array('required' => true, 'label' => 'Apellido'))
                ->add('emailact', 'email', array('required' => true, 'label' => 'form.email', 'translation_domain' => 'FOSUserBundle'))
                ->add('address1', null, array('label' => 'form.address1', 'translation_domain' => 'AppBundle'))
                ->add('address2', null, array('required' => false, 'label' => 'form.address2', 'translation_domain' => 'AppBundle'))
                ->add('locality', null, array('label' => 'form.locality', 'translation_domain' => 'AppBundle'))
                ->add('province', null, array('label' => 'form.province', 'translation_domain' => 'AppBundle'))
                ->add('country', 'country', array('label' => 'form.country', 'translation_domain' => 'AppBundle',
                    'attr' => [
                        'class' => 'input-country'
                    ]
                ))
                ->add('postalcode', null, array('label' => 'form.postalcode', 'translation_domain' => 'AppBundle'))
                ->add('phone', null, array('label' => 'form.phone', 'translation_domain' => 'AppBundle'))
                ->add('website', 'url', array('required' => false, 'label' => 'form.website', 'translation_domain' => 'AppBundle'))
                ->add('birthdate', 'date', [
                    'widget' => 'single_text',
                    'format' => 'dd-MM-yyyy',
                    'attr' => [
                        'class' => 'form-control input-inline datepicker',
                        'data-provide' => 'datepicker',
                        'data-date-format' => 'dd-mm-yyyy'
                    ],
                    'label' => 'form.birthdate', 'translation_domain' => 'AppBundle'
                ])
                ->add('user', 'entity_id', array(
                    'class' => 'AppUserBundle\Entity\User',
                ))
                ->add('timezone', 'timezone', array('label' => 'Zona horaria'))
                ->add('recaptcha', EWZRecaptchaType::class, array(
                    'required' => true, 'language' => 'es'
                        // ...
                ))
        ;
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\Patients'
        ));
    }

    /**
     * @return string
     */
    public function getName() {
        return 'appbundle_patients_act';
    }

}
