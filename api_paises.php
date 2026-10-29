<?php
declare(strict_types=1);

require_once __DIR__ . '/backend/includes/bootstrap.php';
require_once __DIR__ . '/backend/includes/layout.php';

render_header('Importar países', 'api-paises');
?>
<section class="page-heading">
    <div>
        <p class="eyebrow">REST Countries</p>
        <h1>Consultar e cadastrar país</h1>
        <p>Busque um país pelo nome para armazenar seus dados oficiais na base complementar.</p>
    </div>
    <a class="button button-secondary" href="<?= e(url('listar_paises.php')) ?>">Ver países importados</a>
</section>

<?php if (!is_administrator()): ?>
    <div class="flash flash-warning" role="alert">Somente administradores podem importar países. Você pode consultar a listagem já cadastrada.</div>
<?php else: ?>
    <section class="form-card">
        <form action="<?= e(url('importar_pais.php')) ?>" method="post" novalidate>
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <div class="field-group">
                <label for="pais">Nome do país</label>
                <input id="pais" name="pais" type="text" maxlength="100" required placeholder="Ex.: Brazil ou South Africa">
            </div>
            <div class="form-actions">
                <button class="button button-primary" type="submit">Consultar e cadastrar</button>
                <a class="button button-secondary" href="<?= e(url('listar_paises.php')) ?>">Ver listagem</a>
            </div>
        </form>
    </section>
<?php endif; ?>
<?php render_footer(); ?>
