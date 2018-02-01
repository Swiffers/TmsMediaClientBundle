<?php

/**
 * @author:  Gabriel BONDAZ <gabriel.bondaz@idci-consulting.fr>
 * @license: MIT
 */

namespace Tms\Bundle\MediaClientBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Tms\Bundle\MediaClientBundle\Model\Media;

class TmsTransformableImageUploadType extends TmsMediaUploadType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) use ($options) {
                $form = $event->getForm();
                $data = $event->getData();
                $metadata = array(
                    'cropper_ratio' => null,
                    'cropper_data' => array(),
                    'cropper_container_data' => array(),
                    'cropper_image_data' => array(),
                    'cropper_canvas_data' => array(),
                    'cropper_crop_box_data' => array(),
                );

                if ($data instanceof Media) {
                    $metadata = $data->getMetadata();
                }

                $form
                    ->add('cropper_ratio', 'hidden', array(
                        'required' => false,
                        'data' => floatval($metadata['cropper_ratio']),
                    ))
                    ->add('cropper_data', 'tms_hidden_json', array(
                        'required' => false,
                        'data' => $metadata['cropper_data'],
                    ))
                    ->add('cropper_container_data', 'tms_hidden_json', array(
                        'required' => false,
                        'data' => $metadata['cropper_container_data'],
                    ))
                    ->add('cropper_image_data', 'tms_hidden_json', array(
                        'required' => false,
                        'data' => $metadata['cropper_image_data'],
                    ))
                    ->add('cropper_canvas_data', 'tms_hidden_json', array(
                        'required' => false,
                        'data' => $metadata['cropper_canvas_data'],
                    ))
                    ->add('cropper_crop_box_data', 'tms_hidden_json', array(
                        'required' => false,
                        'data' => $metadata['cropper_crop_box_data'],
                    ))
                ;
            },
            100
        );
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['container_width'] = $options['container_width'];
        $view->vars['container_height'] = $options['container_height'];
        $view->vars['zoom_attr'] = $options['zoom_attr'];
        $view->vars['rotate_attr'] = $options['rotate_attr'];
        $view->vars['reset_attr'] = $options['reset_attr'];
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        parent::setDefaultOptions($resolver);

        $resolver
            ->setDefaults(array(
                'container_width' => 200,
                'container_height' => 200,
                'zoom_attr' => array(),
                'rotate_attr' => array(),
                'reset_attr' => array('value' => 'reset'),
            ))
            ->setAllowedTypes(array(
                'container_width' => array('integer'),
                'container_height' => array('integer'),
                'zoom_attr' => array('array'),
                'rotate_attr' => array('array'),
                'reset_attr' => array('array'),
            ))
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'tms_transformable_image_upload';
    }
}
