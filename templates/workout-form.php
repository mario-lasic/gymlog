<form action="<?= e($formAction) ?>" method="post">
    <input
            type="hidden"
            name="csrf_token"
            value="<?= e($csrfToken) ?>"
    >

    <div class="input-container">
        <label for="date">Date</label>
        <input
                type="date"
                id="date"
                name="date"
                value="<?= e($date) ?>"
                required
        >

        <?php if (isset($errors['date'])): ?>
            <p><?= e($errors['date']) ?></p>
        <?php endif; ?>
    </div>

    <div class="input-container">
        <label for="name">Name</label>
        <input
                type="text"
                id="name"
                name="name"
                value="<?= e($name) ?>"
                maxlength="100"
                required
        >

        <?php if (isset($errors['name'])): ?>
            <p><?= e($errors['name']) ?></p>
        <?php endif; ?>
    </div>

    <div class="input-container">
        <label for="note">Note</label>
        <textarea
                id="note"
                name="note"
                cols="30"
                rows="10"
                maxlength="5000"
        ><?= e($note) ?></textarea>

        <?php if (isset($errors['note'])): ?>
            <p><?= e($errors['note']) ?></p>
        <?php endif; ?>
    </div>

    <button type="submit"><?= e($submitLabel) ?></button>
</form>
