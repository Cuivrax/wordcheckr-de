<?php

declare(strict_types=1);

/**
 * Aides d'echappement pour les vues (app/View/). Pas de moteur de gabarit ajoute
 * (CLAUDE.md : aucune dependance sans decision dans docs/DECISIONS.md) -- PHP simple,
 * avec un echappement systematique des valeurs issues des donnees.
 */

if (!function_exists('e')) {
    function e(string|int $value): string
    {
        // ENT_SUBSTITUTE (filet de securite, audit independant, docs/DECISIONS.md D-DE-011) :
        // sans ce flag, une sequence UTF-8 invalide (ex. offset octet tombant au milieu d'un
        // caractere Ä/Ö/Ü multi-octets, cause reelle trouvee dans app/View/word.php avant
        // correction -- voir $highlighted) fait renvoyer htmlspecialchars() une CHAINE VIDE,
        // pas un caractere de remplacement -- le contenu attendu disparait silencieusement du
        // HTML rendu au lieu d'afficher un caractere degrade visible. Reste un filet de
        // securite generique pour toute future desynchronisation octet/caractere, pas un
        // correctif a la place de la vraie correction cote appelant.
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
