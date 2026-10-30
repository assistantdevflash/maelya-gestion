/**
 * Éditeur riche Quill — chargé uniquement sur les formulaires qui en ont besoin
 * (fiche produit, composition d'e-mails).
 *
 * Avant, Quill était importé dans `app.js` : ~200 Ko de JavaScript + ~35 Ko de
 * CSS téléchargés sur toutes les pages de l'application, y compris celles qui
 * n'affichent aucun éditeur.
 */
import Quill from 'quill';
import 'quill/dist/quill.snow.css';

window.Quill = Quill;

export default Quill;
