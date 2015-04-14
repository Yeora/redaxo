<header class="rex-page-header">
    <div class="page-header">
        <h1><?= $this->heading ?>
            <?php if (isset($this->subheading) && $this->subheading != ''): ?>
                <small><?= $this->subheading ?></small>
            <?php endif; ?>
        </h1>
    </div>
    <?php if (isset($this->subtitle) && $this->subtitle != ''): ?>
        <nav class="nav rex-page-nav">
            <?= $this->subtitle ?>
        </nav>
    <?php endif; ?>
</header>
