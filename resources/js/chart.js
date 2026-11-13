/**
 * Graphiques du tableau de bord.
 *
 * Chargé à la demande (dynamic import) depuis les vues qui affichent un
 * graphique. Auparavant Chart.js venait d'un CDN : sans réseau — et
 * l'application a un mode hors-ligne — le graphique disparaissait purement
 * et simplement.
 */
import {
    Chart,
    LineController,
    LineElement,
    PointElement,
    LinearScale,
    CategoryScale,
    Filler,
    Tooltip,
    Legend,
} from 'chart.js';

Chart.register(
    LineController,
    LineElement,
    PointElement,
    LinearScale,
    CategoryScale,
    Filler,
    Tooltip,
    Legend
);

window.Chart = Chart;

export default Chart;
