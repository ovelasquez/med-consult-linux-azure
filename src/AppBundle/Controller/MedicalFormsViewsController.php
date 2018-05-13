<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use AppBundle\Entity\MedicalForms;
use AppBundle\Entity\MedicalFormsViews;
use AppBundle\Entity\MedicalFormsFieldsets;
use AppBundle\Form\MedicalFormsViewsType;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/**
 * MedicalFormsViews controller.
 *
 * @Route("/medicalformsviews")
 */
class MedicalFormsViewsController extends Controller {

    /**
     * Lists all MedicalFormsViews entities.
     *
     * @Route("/", name="medicalformsviews")
     * @Method("GET")
     * @Template()
     * @Security("has_role('ROLE_ADMIN')") 
     */
    public function indexAction() {
        $em = $this->getDoctrine()->getManager();

        $entities = $em->getRepository('AppBundle:MedicalFormsViews')->findAll(null, array("specialty" => "ASC", "id" => "DESC"));

        return array(
            'entities' => $entities,
        );
    }
    
    /**
     * Displays a form to edit an existing MedicalFormsViews entity.
     *
     * @Route("/{id}/edit", name="medical_forms_views_edit")
     * @Method("GET")
     * @Template("AppBundle:MedicalFormsViews:createView.html.twig")
     * @Security("has_role('ROLE_ADMIN')") 
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('AppBundle:MedicalFormsViews')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find MedicalFormsViews entity.');
        }

        $editForm = $this->createEditForm($entity);
        //$deleteForm = $this->createDeleteForm($id);

        $entities = $this->getFieldsFormsAdm($entity->getMedicalForm());
        
        $entity->setFields("'".str_replace(',', "','", $entity->getFields())."'");
        $entity->setFieldsets("'".str_replace(',', "','", $entity->getFieldsets())."'");
        $entity->setRequired("'".str_replace(',', "','", $entity->getRequired())."'");

        return array(
            'entity' => $entity,
            'entityf' => $entity->getMedicalForm(),
            'entities' => $entities,
            'form' => $editForm->createView(),
        );
//        return array(
//            'entity'      => $entity,
//            'edit_form'   => $editForm->createView(),
//            'delete_form' => $deleteForm->createView(),
//        );
    }

    /**
    * Creates a form to edit a MedicalFormsViews entity.
    *
    * @param MedicalFormsViews $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createEditForm(MedicalFormsViews $entity)
    {
        $form = $this->createForm(new MedicalFormsViewsType(), $entity, array(
            'action' => $this->generateUrl('medical_forms_views_update', array('id' => $entity->getId())),
            'method' => 'PUT',
        ));
        
        return $form;
    }
    /**
     * Edits an existing MedicalFormsViews entity.
     *
     * @Route("/{id}", name="medical_forms_views_update")
     * @Method("PUT")
     * @Template("AppBundle:MedicalFormsViews:edit.html.twig")
     * @Security("has_role('ROLE_ADMIN')") 
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        
        $entity = $em->getRepository('AppBundle:MedicalFormsViews')->find($id);

        if (!$entity) {  throw $this->createNotFoundException('Unable to find MedicalFormsViews entity.');  }
        
        $form = $this->createEditForm($entity);
        $form->handleRequest($request);
   
        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            
            if (is_array($request->request->get("fields"))): $entity->setFields(implode(',',$request->request->get("fields")));
            else:  $entity->setFields($request->request->get("fields")); endif;       
            if (is_array($request->request->get("fieldsets"))): $entity->setFieldsets(implode(',',$request->request->get("fieldsets")));
            else:  $entity->setFieldsets($request->request->get("fieldsets")); endif;               
            
            if (is_array($request->request->get("reque"))): $entity->setRequired(implode(',',$request->request->get("reque")));
            else:  $entity->setRequired($request->request->get("reque")); endif; 
            
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('medical_forms_views_edit', array('id' => $entity->getId())));
        }

        $entityf = $em->getRepository('AppBundle:MedicalForms')->find($entity->getMedicalForm()->getId());
        $entities = $this->getFieldsFormsAdm($entityf);

        return array(
            'entity' => $entity,'entityf' => $entityf, 'entities' => $entities, 'form' => $form->createView(),
        );
    }
    
    
    /**
     * Creates a new MedicalFormsView entity.
     *
     * @Route("/save/view", name="medicalformsview_create")
     * @Method("POST")
     * @Template("AppBundle:MedicalFormsViews:createView.html.twig")
     * @Security("has_role('ROLE_ADMIN')") 
     */
    public function createAction(Request $request) {
        $entity = new MedicalFormsViews();
        $form = $this->createForm(new MedicalFormsViewsType(), $entity, array(
            'action' => $this->generateUrl('medicalforms_create'), 'method' => 'POST',
        ));
        $form->handleRequest($request);
        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();            
            if (is_array($request->request->get("fields"))): $entity->setFields(implode(',',$request->request->get("fields")));
            else:  $entity->setFieldsets($request->request->get("fields")); endif;       
            if (is_array($request->request->get("fieldsets"))): $entity->setFieldsets(implode(',',$request->request->get("fieldsets")));
            else:  $entity->setFieldsets($request->request->get("fieldsets")); endif;    
            $entity->setFormName($entity->getMedicalForm()->getFormName()."_view");
            $em->persist($entity);
            $em->flush();
            
            $connection = $em->getConnection();
            $name = "_mffd_" . $entity->getFormName();

            try {
                $table = new \Doctrine\DBAL\Schema\Table($name);
                $table->addColumn('id', 'bigint', array('autoincrement' => true));
                $table->addColumn('medical_forms_field_name', 'string', array('length' => 42, 'customSchemaOptions' => array('collation' => 'utf8_general_ci')));
                $table->addColumn('value_data', 'text');
                $table->addColumn('fos_user_id', 'bigint');
                $table->addColumn('consultation_id', 'bigint');
                $table->addColumn('date_creation', 'datetime');
                $table->addColumn('key_enc', 'string', array('length' => 32, 'customSchemaOptions' => array('collation' => 'utf8_general_ci')));
                $table->setPrimaryKey(array('id'));
                $table->addUniqueIndex(array('medical_forms_field_name', 'fos_user_id','consultation_id'));
                foreach ($connection->getDatabasePlatform()->getCreateTableSQL($table) AS $sql) {
                    $connection->executeQuery($sql);
                }
            } catch (\Exception $e) {
                //throw $this->createNotFoundException('Unable to find MedicalForms entity. ' . $e);
            }
            
            return $this->redirect($this->generateUrl('medical_forms_views_edit', array('id' => $entity->getId())));
        }

        $entityf = $entity->getMedicalForm();
        $entities = $this->getFieldsFormsAdm($entityf);

        return array(
            'entity' => $entity, 'entityf' => $entityf, 'entities' => $entities, 'form' => $form->createView(),
        );
    }

    /**
     * Finds and displays a MedicalForms entity.
     *
     * @Route("/{id}/create/view", name="medicalformsview_new")
     * @Method("GET")
     * @Template()
     * @Security("has_role('ROLE_ADMIN')") 
     */
    public function createViewAction($id) {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('AppBundle:MedicalForms')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find MedicalForms entity.');
        }

        $entities = $this->getFieldsFormsAdm($entity);

        $entityV = new \AppBundle\Entity\MedicalFormsViews();
        $entityV->setMedicalForm($entity);

        $form = $this->createForm(new MedicalFormsViewsType(), $entityV, array(
            'action' => $this->generateUrl('medicalformsview_create'),
            'method' => 'POST',
        ));



//        echo"<pre>";
//        \Doctrine\Common\Util\Debug::dump($entities);
//        echo"</pre>";
//        exit();


        return array(
            'entity' => $entityV,
            'entityf' => $entity,
            'entities' => $entities,
            'form' => $form->createView(),
        );
    }
    
    
    
    

    public function getFieldsFormsAdm($entity) {

        $em = $this->getDoctrine()->getManager();

        $entities = $em->getRepository('AppBundle:MedicalFormsFieldsets')->findBy(array("medicalForm" => $entity->getId()), array("position" => "ASC"));
        $entitiesAll = array();
        $entitiesbyPage = array();
        $entityset = (object) array("fieldset" => '', "fields" => '');
        $classColor = array("azulOscuro blancoColor", "celeste", "rojo", "gris", "lila", "celeste", "rojoFuerte", "azulNormal");
        $itc = 0;
        foreach ($entities as $entityFs) :
            $classC = ($entityFs->getType() == "page") ? $classColor[$itc] : "";
            $entityset = (object) array("fieldset" => '', "fields" => '', "classColor" => $classC);
            $itc = ($entityFs->getType() == "page") ? (($itc === count($classColor) - 1) ? 0 : $itc + 1) : $itc;
            $entityset->fieldset = $entityFs;
            $rsm = new ResultSetMappingBuilder($em);
            $rsm->addRootEntityFromClassMetadata('AppBundle\Entity\MedicalFormsFields', 'f');
            $query = $em->createNativeQuery(""
                    //. "SELECT F3.*,(CASE WHEN oi IS NULL THEN F3.orderid ELSE oi END) as oi ,(CASE F3.id WHEN F2.id THEN 1 ELSE 2 END) AS og FROM medical_forms_fields F3 LEFT JOIN( SELECT F1.subgroup, F1.orderid as oi, F1.id FROM medical_forms_fields F1 WHERE F1.field='group' order by F1.orderid) F2 ON F2.subgroup=F3.subgroup WHERE F3.medical_forms_fieldset_id=:id ORDER BY oi ASC,  og  ASC, orderid ASC "
                    . "CALL GetFieldsByFieldset(:id)", $rsm);
            $query->setParameter('id', $entityFs->getId());
            $entitiesFl = $query->getResult();
            $entityset->fields = $entitiesFl;
            array_push($entitiesAll, $entityset);

            if ($entityFs->getType() != "page"):
                if ($entityFs->getPage() !== null):
                    $entitiesbyPage[$entityFs->getPage()->getId()][$entityFs->getId()] = $entityset;
                else:
                    $entitiesbyPage[$entityFs->getId()]['field'] = $entityFs;
                    $entitiesbyPage[$entityFs->getId()][$entityFs->getId()] = $entityset;
                endif;
            else:
                $entitiesbyPage[$entityFs->getId()]['field'] = $entityFs;
            endif;


        endforeach;

        return $entitiesbyPage;
    }

    function dirSize($directory) {
        $size = 0;
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory)) as $file) {
            $size+=$file->getSize();
        }
        return $size;
    }

    function clDir($files, $dir) {
        if (is_dir($dir)):
            $iterator = new \DirectoryIterator($dir);
            foreach ($iterator as $fileinfo) {
                if ($fileinfo->isFile()) {
                    if ($this->ras($fileinfo->getFilename(), $files) === false):
                        unlink($fileinfo->getPathname());
                    endif;
                }
            }
        endif;
    }

    /**
     * Recursive array search.
     *
     * See http://php.net/manual/en/function.array-search.php#91365
     *
     * @param $needle
     *   The searched value.
     * @param $haystack
     *   The array.
     *
     * @return bool|int|string
     *   Array of keys, containing values or FALSE if not found.
     */
    private function ras($needle, $haystack) {
        $keys = array();
        foreach ($haystack as $key => $value) {
            if ($needle === $value OR ( is_array($value) && $this->ras(
                            $needle, $value
                    ) !== FALSE)
            ) {
                $keys[] = $key;
            }
        }
        if (!empty($keys)) {
            return $keys;
        }

        return FALSE;
    }

}
