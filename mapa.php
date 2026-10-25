<?php
declare(strict_types=1);

require_once __DIR__ . '/backend/includes/bootstrap.php';
require_once __DIR__ . '/backend/includes/layout.php';

render_header('Mapa da ETEC', 'mapa');
?>
<section class="page-heading">
    <div>
        <p class="eyebrow">Localização</p>
        <h1>ETEC de São José dos Campos</h1>
        <p>Confira a localização da sede da escola no mapa.</p>
    </div>
</section>

<section class="content-card map-card" aria-labelledby="mapa-titulo">
    <div class="map-card-header">
        <div>
            <h2 id="mapa-titulo">Como chegar</h2>
            <p>Coordenadas: -23.219814763410596, -45.90671467482335</p>
        </div>
        <a class="button button-secondary" href="https://www.google.com/maps?q=-23.219814763410596,-45.90671467482335" target="_blank" rel="noopener noreferrer">Abrir no Google Maps</a>
    </div>
    <div id="mapa-etec" class="google-map" role="application" aria-label="Mapa com a localização da ETEC de São José dos Campos">
        <p>Carregando mapa…</p>
    </div>
    <noscript>
        <p class="map-fallback">Ative o JavaScript para visualizar o mapa ou use o link “Abrir no Google Maps”.</p>
    </noscript>
</section>

<script>
    window.initMapaEtec = function () {
        const localizacaoEtec = { lat: -23.219814763410596, lng: -45.90671467482335 };
        const mapa = new google.maps.Map(document.getElementById('mapa-etec'), {
            center: localizacaoEtec,
            zoom: 17,
            mapTypeControl: false,
            streetViewControl: false,
            fullscreenControl: true
        });

        new google.maps.Marker({
            position: localizacaoEtec,
            map: mapa,
            title: 'ETEC de São José dos Campos'
        });
    };
</script>
<script async src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDP3Fb7XDNMLFz1GvTC3iWmEja4x7RoHgo&callback=initMapaEtec"></script>
<?php render_footer(); ?>
