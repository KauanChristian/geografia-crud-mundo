<?php
declare(strict_types=1);

require_once __DIR__ . '/backend/includes/bootstrap.php';
require_once __DIR__ . '/backend/includes/layout.php';

$errors = pull_password_form_errors();
$user = current_user();
render_header('Alterar senha');
?>
<section class="page-heading password-heading">
    <div>
        <p class="eyebrow">Segurança da conta</p>
        <h1><?= password_change_required() ? 'Defina sua nova senha' : 'Alterar senha' ?></h1>
        <p><?= password_change_required()
            ? 'Este é seu primeiro acesso. Para continuar, substitua a senha inicial.'
            : 'Atualize sua senha de acesso quando necessário.' ?></p>
    </div>
</section>

<section class="form-card password-card" aria-labelledby="password-form-title">
    <h2 id="password-form-title">Senha de <?= e($user['nome'] ?? 'usuário') ?></h2>
    <p class="form-note">Use no mínimo 8 caracteres, incluindo letra maiúscula, minúscula e número.</p>

    <?php if ($errors !== []): ?>
        <div class="form-error-summary" role="alert" tabindex="-1" data-error-summary>
            <h2>Revise os dados informados</h2>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= e($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form class="entity-form" action="<?= e(url('backend/actions/alterar_senha.php')) ?>" method="post">
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
        <div class="field<?= isset($errors['senha_atual']) ? ' has-error' : '' ?>">
            <label for="senha_atual">Senha atual <span class="required">*</span></label>
            <input id="senha_atual" name="senha_atual" type="password" autocomplete="current-password" required>
            <?php if (isset($errors['senha_atual'])): ?><span class="field-error"><?= e($errors['senha_atual']) ?></span><?php endif; ?>
        </div>
        <div class="field<?= isset($errors['nova_senha']) ? ' has-error' : '' ?>">
            <label for="nova_senha">Nova senha <span class="required">*</span></label>
            <input id="nova_senha" name="nova_senha" type="password" minlength="8" autocomplete="new-password" required>
            <?php if (isset($errors['nova_senha'])): ?><span class="field-error"><?= e($errors['nova_senha']) ?></span><?php endif; ?>
        </div>
        <div class="field<?= isset($errors['confirmacao_senha']) ? ' has-error' : '' ?>">
            <label for="confirmacao_senha">Confirme a nova senha <span class="required">*</span></label>
            <input id="confirmacao_senha" name="confirmacao_senha" type="password" minlength="8" autocomplete="new-password" required>
            <?php if (isset($errors['confirmacao_senha'])): ?><span class="field-error"><?= e($errors['confirmacao_senha']) ?></span><?php endif; ?>
        </div>
        <div class="form-actions">
            <?php if (!password_change_required()): ?><a class="button button-secondary" href="<?= e(url('index.php')) ?>">Cancelar</a><?php endif; ?>
            <button class="button button-primary" type="submit">Atualizar senha</button>
        </div>
    </form>
</section>
<?php render_footer(); ?>
