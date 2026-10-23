<?php

namespace App\Controller;

use App\Service\DashboardStatsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class DashboardController extends AbstractController
{
    #[Route('/admin', name: 'admin_dashboard')]
    public function index(DashboardStatsService $stats): Response
    {
        $boutique = $this->getUser()?->getBoutique();
        if (!$boutique) {
            throw $this->createAccessDeniedException('Aucune boutique associée à ce compte.');
        }

        return $this->render('admin/dashboard.html.twig', [
            'boutique' => $boutique,
            'stats' => $stats->collecter($boutique, 30),
        ]);
    }

    #[Route('/admin/api/stats', name: 'admin_api_stats', methods: ['GET'])]
    public function api(Request $request, DashboardStatsService $stats): JsonResponse
    {
        $boutique = $this->getUser()?->getBoutique();
        if (!$boutique) {
            return new JsonResponse(['erreur' => 'Aucune boutique'], 403);
        }

        $reponse = new JsonResponse($stats->collecter($boutique, $request->query->getInt('jours', 30)));
        $reponse->headers->set('Cache-Control', 'no-store');

        return $reponse;
    }
}
