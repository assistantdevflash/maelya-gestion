<?php

namespace App\Policies;

use App\Models\Commande;
use App\Models\User;

class CommandePolicy
{
    /**
     * Déterminer si l'utilisateur peut voir la liste des commandes
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'employe']);
    }

    /**
     * Déterminer si l'utilisateur peut voir une commande
     */
    public function view(User $user, Commande $commande): bool
    {
        return $user->institut_id === $commande->institut_id && 
               in_array($user->role, ['admin', 'employe']);
    }

    /**
     * Déterminer si l'utilisateur peut modifier une commande
     */
    public function update(User $user, Commande $commande): bool
    {
        return $user->institut_id === $commande->institut_id && 
               $user->role === 'admin';
    }

    /**
     * Déterminer si l'utilisateur peut supprimer une commande
     */
    public function delete(User $user, Commande $commande): bool
    {
        return $user->institut_id === $commande->institut_id && 
               $user->role === 'admin';
    }
}
