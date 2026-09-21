<form action="<?= htmlspecialchars($formAction, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" method="post">
    <input
        type="hidden"
        name="csrf_token"
        value="<?= htmlspecialchars($csrfToken, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
    >

    <div class="input-container">
        <label for="date">Date</label>
        <input
            type="date"
            id="date"
            name="date"
            value="<?= htmlspecialchars($date, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
            required
        >

        <?php
        if (isset($errors['date'])): ?>
            <p><?= htmlspecialchars($errors['date'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
        <?php
        endif; ?>
    </div>

    <div class="input-container">
        <label for="name">Name</label>
        <input
            type="text"
            id="name"
            name="name"
            value="<?= htmlspecialchars($name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
            maxlength="100"
            required
        >

        <?php
        if (isset($errors['name'])): ?>
            <p><?= htmlspecialchars($errors['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
        <?php
        endif; ?>
    </div>

    <div class="input-container">
        <label for="note">Note</label>
        <textarea
            id="note"
            name="note"
            cols="30"
            rows="10"
            maxlength="5000"
        ><?= htmlspecialchars($note, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></textarea>

        <?php
        if (isset($errors['note'])): ?>
            <p><?= htmlspecialchars($errors['note'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
        <?php
        endif; ?>
    </div>

    <button type="submit"><?= htmlspecialchars($submitLabel, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></button>
</form>