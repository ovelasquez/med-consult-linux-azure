<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use AppBundle\Entity\MedicalFormsFields;
use AppBundle\Entity\MedicalFormsFieldsets;
use AppBundle\Form\MedicalFormsFieldsType;
use AppBundle\Form\MedicalFormsFieldsUpdateType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;


/**
 * MedicalFormsFields controller.
 *
 * @Route("/medicalformsfields")
 */
class MedicalFormsFieldsController extends Controller {

    /**
     * Lists all MedicalFormsFields entities.
     *
     * @Route("/", name="medicalformsfields")
     * @Method("GET")
     * @Template()
     * @Security("has_role('ROLE_ADMIN')") 
     */
    public function indexAction() {
        $em = $this->getDoctrine()->getManager();

        $entities = $em->getRepository('AppBundle:MedicalFormsFields')->findAll();

        return array(
            'entities' => $entities,
        );
    }

    /**
     * Lists all MedicalFormsFields entities.
     *
     * @Route("/{ids}/list", name="medicalformsfields_list_set")
     * @Method("GET")
     * @Template()
     * @Security("has_role('ROLE_ADMIN')") 
     */
    public function listAction($ids) {
        $em = $this->getDoctrine()->getManager();
        $entitySet = $em->getRepository('AppBundle:MedicalFormsFieldsets')->find($ids);
        
        if (!$entitySet) {
            throw $this->createNotFoundException('Unable to find MedicalFormsFieldsSet entity.');
        }

        $rsm = new ResultSetMappingBuilder($em);
        $rsm->addRootEntityFromClassMetadata('AppBundle\Entity\MedicalFormsFields', 'f');
        $query = $em->createNativeQuery("CALL GetFieldsByFieldset(:id) ", $rsm);
        $query->setParameter('id', $ids);
        $entities = $query->getResult();
        return array(
            'entities' => $entities, 'ids' => $ids,'idf' => (isset($entitySet)) ? $entitySet->getMedicalForm()->getId() : "", 'nameset' => (isset($entitySet)) ? $entitySet->getLabel() : "",
        );
    }

    /**
     * Creates a new MedicalFormsFields entity.
     *
     * @Route("/", name="medicalformsfields_create")
     * @Method("POST")
     * @Template("AppBundle:MedicalFormsFields:newF.html.twig")
     * @Security("has_role('ROLE_ADMIN')") 
     */
    public function createAction(Request $request) {
        $entity = new MedicalFormsFields();
        $req = $request->request;
        $form = $this->createCreateFForm($entity, $req->get("appbundle_medicalformsfields")['medicalFormsFieldset']);
        $form->handleRequest($request);

        $config = json_encode($req->get("field_conf"));

        if ($form->isValid()) {
            $entity->setConfig($config);
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            if ($entity->getField() == "group" || $entity->getField() == "grid") {
                if ($entity->getSubgroup()===''):
                    $entity->setSubgroup($entity);
                endif;
                    
                $em->persist($entity);
                $em->flush();
            }
            
            $sql = "UPDATE medical_forms_fields SET orderid = ((SELECT ord FROM (SELECT MAX(orderid) AS ord FROM medical_forms_fields) AS ord_s) + 1)  WHERE medical_forms_fields.id = :id";
            
            $connection = $em->getConnection();
            $statement = $connection->prepare($sql);
            $statement->bindValue('id', $entity->getId());
            $statement->execute();
            
            return $this->redirect($this->generateUrl('medicalformsfields_list_set', array('ids' => $entity->getMedicalFormsFieldset()->getId())));
        }

        return array(
            'entity' => $entity,
            'form' => $form->createView(),
            'ids' => $entity->getMedicalFormsFieldset()->getId(),
        );
    }

    /**
     * Creates a form to create a MedicalFormsFields entity.
     *
     * @param MedicalFormsFields $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(MedicalFormsFields $entity) {
        $form = $this->createForm(new MedicalFormsFieldsType(), $entity, array(
            'action' => $this->generateUrl('medicalformsfields_create'),
            'method' => 'POST',
        ));

        $form->add('submit', 'submit', array('label' => 'Crear'));

        return $form;
    }

    /**
     * Displays a form to create a new MedicalFormsFields entity.
     *
     * @Route("/new", name="medicalformsfields_new")
     * @Method("GET")
     * @Template()
     * @Security("has_role('ROLE_ADMIN')") 
     */
    public function newAction() {
        $entity = new MedicalFormsFields();
        $form = $this->createCreateForm($entity);

        return array(
            'entity' => $entity,
            'form' => $form->createView(),
        );
    }

    /**
     * Creates a form to create a MedicalFormsFields entity.
     *
     * @param MedicalFormsFields $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateFForm(MedicalFormsFields $entity, $ids) {
        $form = $this->createForm(new MedicalFormsFieldsType(), $entity, array(
            'action' => $this->generateUrl('medicalformsfields_create'),
            'method' => 'POST',
            'ids' => $ids,
        ));

        $em = $this->getDoctrine()->getManager();
        $entityFs = $em->getRepository('AppBundle:MedicalFormsFieldsets')->find($ids);
        
        if (!$entityFs) {
            throw $this->createNotFoundException('Unable to find MedicalFormsFieldset entity.');
        }

        $form->get('medicalFormsFieldset')->setData($entityFs);

        $form->add('submit', 'submit', array('label' => 'Crear'));

        return $form;
    }

    /**
     * Displays a form to create a new MedicalFormsFields entity.
     *
     * @Route("/new/{idf}/{ids}", name="medicalformsfields_new_f")
     * @Method("GET")
     * @Template()
     * @Security("has_role('ROLE_ADMIN')") 
     */
    public function newFAction($idf, $ids) {
        $entity = new MedicalFormsFields();
        $form = $this->createCreateFForm($entity, $ids);

        return array(
            'entity' => $entity,
            'form' => $form->createView(),
            'ids' => $ids,
        );
    }

    /**
     * Finds and displays a MedicalFormsFields entity.
     *
     * @Route("/{id}", name="medicalformsfields_show")
     * @Method("GET")
     * @Template()
     * @Security("has_role('ROLE_ADMIN')") 
     */
    public function showAction($id) {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('AppBundle:MedicalFormsFields')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find MedicalFormsFields entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity' => $entity,
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Displays a form to edit an existing MedicalFormsFields entity.
     *
     * @Route("/{id}/edit", name="medicalformsfields_edit")
     * @Method("GET")
     * @Template()
     * @Security("has_role('ROLE_ADMIN')") 
     */
    public function editAction($id) {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('AppBundle:MedicalFormsFields')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find MedicalFormsFields entity.');
        }

        $editForm = $this->createEditForm($entity);
        $deleteForm = $this->createDeleteForm($id);

        $baseF = new \AppBundle\Utils\BaseFields();
        $class_methods = get_class_methods($baseF);
        $config = json_decode($entity->getConfig());

        if (array_search($entity->getField() . "Field", $class_methods) !== false):
            $config = call_user_func(array($baseF, $entity->getField() . "Field"), $config);
        endif;

        return array(
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
            'config' => $config,
        );
    }

    /**
     * Creates a form to edit a MedicalFormsFields entity.
     *
     * @param MedicalFormsFields $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createEditForm(MedicalFormsFields $entity) {
        $form = $this->createForm(new MedicalFormsFieldsUpdateType(), $entity, array(
            'action' => $this->generateUrl('medicalformsfields_update', array('id' => $entity->getId())),
            'method' => 'PUT',
        ));

        $form->add('submit', 'submit', array('label' => 'Update'));

        return $form;
    }

    /**
     * Edits an existing MedicalFormsFields entity.
     *
     * @Route("/{id}", name="medicalformsfields_update")
     * @Method("PUT")
     * @Template("AppBundle:MedicalFormsFields:edit.html.twig")
     * @Security("has_role('ROLE_ADMIN')") 
     */
    public function updateAction(Request $request, $id) {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('AppBundle:MedicalFormsFields')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find MedicalFormsFields entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $req = $request->request;
            $config = json_encode($req->get("field_conf"));
            $entity->setConfig($config);

            $em->flush();

            return $this->redirect($this->generateUrl('medicalformsfields_edit', array('id' => $id)));
        }

        return array(
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Deletes a MedicalFormsFields entity.
     *
     * @Route("/{id}", name="medicalformsfields_delete")
     * @Method("DELETE")
     * @Security("has_role('ROLE_ADMIN')") 
     */
    public function deleteAction(Request $request, $id) {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('AppBundle:MedicalFormsFields')->find($id);

            $ids = $entity->getMedicalFormsFieldset()->getId();

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find MedicalFormsFields entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('medicalformsfields_list_set', array('ids' => $ids)));
    }

    /**
     * Creates a form to delete a MedicalFormsFields entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id) {
        return $this->createFormBuilder()
                        ->setAction($this->generateUrl('medicalformsfields_delete', array('id' => $id)))
                        ->setMethod('DELETE')
                        ->add('submit', 'submit', array('label' => 'Delete'))
                        ->getForm()
        ;
    }

    /**
     * @return JsonResponse
     */
    public function loadsettingsAction($field, $name) {
        $baseF = new \AppBundle\Utils\BaseFields();
        $class_methods = get_class_methods($baseF);
        $config = NULL;
        if (array_search($field . "Field", $class_methods) !== false):
            $config = call_user_func(array($baseF, $field . "Field"), $name);
        endif;
        return new JsonResponse($config);
    }

}
