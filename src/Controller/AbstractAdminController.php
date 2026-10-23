<?php

namespace App\Controller;

use App\Entity\Boutique;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

abstract class AbstractAdminController extends AbstractController
{
    protected function maBoutique(): Boutique
    {
        $user = $this->getUser();
        if (!$user instanceof User || !$user->getBoutique()) {
            throw $this->createAccessDeniedException('Aucune boutique associée à ce compte.');
        }
        return $user->getBoutique();
    }
}
