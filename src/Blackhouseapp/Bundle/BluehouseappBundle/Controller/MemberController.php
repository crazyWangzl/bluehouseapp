<?php
/**
 * Created by PhpStorm.
 * User: John
 * Date: 14-10-13
 * Time: 下午2:04
 */

namespace Blackhouseapp\Bundle\BluehouseappBundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Blackhouseapp\Bundle\BluehouseappBundle\Entity\Member;
use Blackhouseapp\Bundle\BluehouseappBundle\Form\MemberType;
use Blackhouseapp\Bundle\BluehouseappBundle\Form\MemberImageType;


class MemberController  extends Controller
{

    /**
     * @Route("/member/needAvatarImage",name="member_needAvatarImage")
     * @Template("BlackhouseappBluehouseappBundle:Member:needAvatarImage.html.twig")
     * @Method({"GET"})
     */
    public function needAvatarImageAction(Request $request)
    {
        return array();
    }
    /**
     * @Route("/member/edit",name="member_edit")
     * @Template()
     * @Method({"GET"})
     */
    public function editAction(Request $request)
    {

        $current = $this->get('security.context')->getToken()->getUser();
        $member = $this->getDoctrine()->getManager()
            ->getRepository('BlackhouseappBluehouseappBundle:Member')
            ->find($current->getId());
        if(!$member->getNickname()){
            $member->setNickname($member->getUsername());
        }
        $isEdit = $member->getAvatar()!='';
        $memberType = new MemberType();
        $form = $this->createForm($memberType,$member,array(
            'action'=>$this->generateUrl('member_update'),
            'method'=>'POST'
        ));

        $memberImageType = new MemberImageType($isEdit);
        $memberImageForm = $this->createForm($memberImageType,$member,array(
            'action'=>$this->generateUrl('member_update_image'),
            'method'=>'POST'
        ));



        $param['member']=$member;
        $param['form']=$form->createView();
        $param['memberImageForm']=$memberImageForm->createView();
        return $param;
    }

    /**
     * @Route("/member/updateImage",name="member_update_image")
     * @Template("BlackhouseappBluehouseappBundle:Member:edit.html.twig")
     * @Method({"PUT","POST"})
     */
    public function updateMemberImageAction(Request $request)
    {
        $current = $this->get('security.context')->getToken()->getUser();
        $member = $this->getDoctrine()->getManager()
            ->getRepository('BlackhouseappBluehouseappBundle:Member')
            ->find($current->getId());

        $memberType = new MemberType();
        $form = $this->createForm($memberType,$member,array(
            'action'=>$this->generateUrl('member_update'),
            'method'=>'POST'
        ));


        $isEdit = $member->getAvatar()!='';
        $memberType = new MemberImageType($isEdit);
        $memberImageForm = $this->createForm($memberType,$member,array(
            'action'=>$this->generateUrl('member_update_image'),
            'method'=>'POST'
        ));
        $memberImageForm->handleRequest($request);
        if($memberImageForm->isValid()){
            $em = $this->getDoctrine()->getManager();
            $member->setModified(new \DateTime());
            $em->persist($member);
            $em->flush();
            $this->get('session')->getFlashBag()->add('success','保存成功');
            return $this->redirect($this->generateUrl('member_edit'));
        }
        $param['member']=$member;
        $param['form']=$form->createView();
        $param['memberImageForm']=$memberImageForm->createView();
        return $param;
    }


    /**
     * @Route("/member/update",name="member_update")
     * @Template("BlackhouseappBluehouseappBundle:Member:edit.html.twig")
     * @Method({"PUT","POST"})
     */
    public function updateAction(Request $request)
    {
        $current = $this->get('security.context')->getToken()->getUser();
        $member = $this->getDoctrine()->getManager()
            ->getRepository('BlackhouseappBluehouseappBundle:Member')
            ->find($current->getId());

        $isEdit = $member->getAvatar()!='';
        $memberType = new MemberImageType($isEdit);
        $memberImageForm = $this->createForm($memberType,$member,array(
            'action'=>$this->generateUrl('member_update_image'),
            'method'=>'POST'
        ));


        $memberType = new MemberType();
        $form = $this->createForm($memberType,$member,array(
            'action'=>$this->generateUrl('member_update'),
            'method'=>'POST'
        ));

        $form->handleRequest($request);
        if($form->isValid()){
            $em = $this->getDoctrine()->getManager();
            $member->setModified(new \DateTime());
            $em->persist($member);
            $em->flush();
            $this->get('session')->getFlashBag()->add('success','保存成功');
            return $this->redirect($this->generateUrl('member_edit'));
        }
        $param['member']=$member;
        $param['form']=$form->createView();
        $param['memberImageForm']=$memberImageForm->createView();

        return $param;
    }

    /**
     * Finds and displays a member entity.
     *
     * @Route("/user/{username}", name="user_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction(Request $request, $username)
    {
        $param=array();
        $em = $this->getDoctrine()->getManager();

        $commentRepo = $em->getRepository('BlackhouseappBluehouseappBundle:Member');
        $query = $commentRepo->createQueryBuilder('m')
            ->where('m.username = :username')
            ->andWhere('m.locked = :locked')
            ->setParameters(array(':username'=>$username,':locked'=>false))
            ->setMaxResults(1)
            ->setFirstResult(0)
            ->getQuery();
        $entity =null;
        try {
            $entity = $query->getSingleResult();
        } catch (\Doctrine\Orm\NoResultException $e) {
            $entity = null;
        }


        if (!$entity) {
            throw $this->createNotFoundException('这个用户不存在');
        }



        $posts = $this->get('blackhouseapp_bluehouseapp.post')->getPostsByMember($entity);

        $lastComments = array();
        foreach ($posts  as $post){
            $lastComments[$post->getId()]=$this->get('blackhouseapp_bluehouseapp.post')->getLastComment($post);

        }



        $postComments = $this->get('blackhouseapp_bluehouseapp.post')->getPostCommentsByMember($entity);


        $param['member'] = $entity;
        $param['posts'] = $posts;
        $param['lastComments'] = $lastComments;
        $param['postComments'] = $postComments;
        return $param;
    }


    /**
     * @Route("/admin/members/list/{locked}",name="members_list")
     * @Template()
     * @Method({"GET"})
     */
    public function listAction(Request $request,$locked=0)
    {

        $em = $this->getDoctrine()->getManager();

        $page = $request->query->get('page', 1);
        $repo = $em->getRepository('BlackhouseappBluehouseappBundle:Member');

        $query = $repo->createQueryBuilder('a')
            ->orderBy('a.modified', 'desc')
            ->where('a.locked = :locked')
            ->setParameters(array('locked' => $locked))
            ->getQuery();

        $entities = $this->get('knp_paginator')->paginate($query, $page, 50);



        $qb = $repo->createQueryBuilder('a');
        $qb->select('COUNT(a)');
        $qb->where('a.locked = :locked');

        $qb->setParameter('locked', false);
        $activeCount = $qb->getQuery()->getSingleScalarResult();

        $qb->setParameter('locked', true);
        $inactiveCount = $qb->getQuery()->getSingleScalarResult();

        return array(
            'entities' => $entities,
            'activeCount' => $activeCount,
              'inactiveCount' => $inactiveCount

        );
    }


    /**
 * @Route("/admin/member/enable/{id}",name="member_enable")
 * @Method({"GET"})
 */
    public function enableAction(Request $request,$id)
    {
        $member = $this->getDoctrine()->getManager()
            ->getRepository('BlackhouseappBluehouseappBundle:Member')
            ->find($id);

        $em = $this->getDoctrine()->getManager();
        $member->setLocked(false);
        $em->flush($member);
        return $this->redirect($this->generateUrl('members_list'));
    }

    /**
     * @Route("/admin/member/disable/{id}",name="member_disable")
     * @Method({"GET"})
     */
    public function disableAction(Request $request,$id)
    {
        $member = $this->getDoctrine()->getManager()
            ->getRepository('BlackhouseappBluehouseappBundle:Member')
            ->find($id);
        $em = $this->getDoctrine()->getManager();
        $member->setLocked(true);
        $em->flush($member);

        return $this->redirect($this->generateUrl('members_list'));
    }


} 