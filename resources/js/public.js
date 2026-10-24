/**
 * Bundle Alpine LOCAL pour les pages publiques (boutique, vitrine, avis…).
 *
 * Pourquoi un bundle dédié ?
 * - Les pages publiques chargeaient Alpine depuis un CDN externe (unpkg / jsDelivr).
 *   En cas de lenteur, de blocage ou de panne du CDN, Alpine ne démarrait jamais :
 *   la page restait masquée par `x-cloak` → « aucun produit ne s'affiche ».
 * - On ne réutilise pas `app.js` car celui-ci embarque Quill + la caisse (lourds et
 *   inutiles pour une boutique publique).
 *
 * Ne pas importer ce fichier dans le dashboard/admin : Livewire y fournit déjà Alpine.
 */
import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();
