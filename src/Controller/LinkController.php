<?php
/**
 * Created by PhpStorm.
 * User: m.ogeret
 * Date: 09/04/2018
 * Time: 13:07
 */

namespace App\Controller;

use App\Entity\Link;
use App\Entity\LogLink;
use App\Form\LinkReviewType;
use App\Service\LinkManager;
use App\Service\UriManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class LinkController extends AbstractController
{
    private $uriManager;
    private $linkManager;

    /**
     * LinkController constructor.
     * @param UriManager $uriManager
     * @param LinkManager $linkManager
     */
    public function __construct(UriManager $uriManager, LinkManager $linkManager)
    {
        $this->uriManager = $uriManager;
        $this->linkManager = $linkManager;
    }

    /**
     * @param $uuid
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function linkHandler(Request $request, $uuid)
    {
        $em = $this->getDoctrine()->getManager();
        /** @var Link $link */
        $link = $em->getRepository(Link::class)->findOneByUuid($uuid);

        if (is_null($link)) {
            return $this->redirectToRoute('app_main_route');
        }

        $log = new LogLink($request, $link);

        $em->persist($log);
        $em->flush();

        return $this->redirect($link->getURL());
    }


    public function linkManager() {
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            $this->forward('web_profiler.controller.profiler:homeAction');
        }

        $em = $this->getDoctrine()->getManager();
        $links = $em->getRepository(Link::class)->getByUser($this->getUser());

        $linksArray = [];

        foreach ($links as $key => $link) {
            /** @var $link Link */
            $linksArray[$key]['id'] = $link->getId();
            $linksArray[$key]['uuid'] = $link->getUuid();
            $linksArray[$key]['url'] = $link->getUrl();
            $linksArray[$key]['datecrea'] = $link->getDatecrea()->format('Y-m-d');
            $linksArray[$key]['visited'] = $link->getLogLink()->count();
        }

        return new JsonResponse($linksArray);
    }

    public function linkManagerController(Request $request, LinkManager $lm) {
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            throw new AccessDeniedException();
        }

        if ($content = $request->getContent()) {
            $linkArray = json_decode($content, true);
        } else {
            throw new \Exception('No parameters found.');
        }
        $method = $request->getMethod();

        if ($method == 'PUT') {
            $link = $lm->createOrUpdate($linkArray, $request, $this->getUser());
            if (is_null($link)) {
                return new JsonResponse('ko');
            }
        } elseif ($method == 'DELETE') {
            $lm->delete($linkArray);
            return new JsonResponse($linkArray);
        }

        $linkArray = [];
        $linkArray['id'] = $link->getId();
        $linkArray['uuid'] = $link->getUuid();
        $linkArray['url'] = $link->getUrl();
        $linkArray['datecrea'] = $link->getDatecrea()->format('Y-m-d');
        $linkArray['visited'] = $link->getLogLink() ? $link->getLogLink()->count() : 0;


        return new JsonResponse($linkArray);
    }

    public function checkUniqueUuid(Request $request)
    {
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            throw new AccessDeniedException();
        }

        $uuid = $request->get('uuid');
        $isUnique = $this->getDoctrine()->getRepository(Link::class)->uniqueUuidCheck($uuid);
        return new JsonResponse($isUnique);
    }

    public function reviewLink($uuid = null, Request $request)
    {
        $reviewForm = $this->createForm(LinkReviewType::class)->handleRequest($request);

        if ($reviewForm->isSubmitted() && $reviewForm->isValid()) {
            $url = $reviewForm->getData()['URL'];
            $uuid = $this->uriManager->getUuidFromUrl($url);
            $link = $this->linkManager->getLinkFromUuid($uuid); /** Link $link */

            return new JsonResponse($this->render('tool/linkreview.html.twig',
                [
                    'status' => 'ok',
                    'reviewForm' => $reviewForm->createView(),
                    'url' => $link->getUrl(),
                    'datecrea' => $link->getDatecrea()
                ]
            )->getContent());
        }

        return new JsonResponse($this->render('tool/linkreview.html.twig',
            [
                'reviewForm' => $reviewForm->createView(),
            ]
            )->getContent());
    }
}
