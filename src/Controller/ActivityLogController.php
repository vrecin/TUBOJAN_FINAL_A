<?php

namespace App\Controller;

use App\Repository\ActivityLogRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/activity/log')]
class ActivityLogController extends AbstractController
{
    #[Route('', name: 'app_activity_log')]
    public function index(Request $request, ActivityLogRepository $repo): Response
    {
        
         if ($this->isGranted('ROLE_STAFF') && !$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'You do not have access to the admin dashboard.');
            return $this->redirectToRoute('app_product_index');
        }

        $username = $request->query->get('user');
        $action = $request->query->get('action');
        $date = $request->query->get('date');
        $page = max(1, (int)$request->query->get('page', 1));
        $limit = 20;

        $logs = $repo->filterLogs($username, $action, $date, $page, $limit);
        $total = $repo->countFilteredLogs($username, $action, $date);
        $pageCount = ceil($total / $limit);

        return $this->render('activity_log/index.html.twig', [
            'logs' => $logs,
            'currentPage' => $page,
            'pageCount' => $pageCount,
        ]);
    }

    #[Route('/{id}', name: 'admin_activity_logs_show', requirements: ['id' => '\d+'])]
    public function show(int $id, ActivityLogRepository $repo): Response
    {
        $log = $repo->find($id);

        if (!$log) {
            throw $this->createNotFoundException('Log entry not found.');
        }
 
        return $this->render('activity_log/show.html.twig', [
            'log' => $log,
        ]);
    }
}
