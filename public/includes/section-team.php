<?php

declare(strict_types=1);

require_once __DIR__ . '/site-team.php';

$team = site_team_all();
if (count($team) === 0) {
    return;
}

$teamHome = array_slice($team, 0, 4);

?>
<section class="team" aria-labelledby="team-title">
    <div class="container">
        <header class="team__header">
            <div class="team__intro">
                <h2 class="team__title" id="team-title">Наша команда</h2>
                <p class="team__lead">С вами работают проверенные специалисты в Иркутске — риэлторы, юристы и ипотечные брокеры в одном офисе.</p>
            </div>
            <a class="team__more" href="/about/#team">Вся команда</a>
        </header>

        <div class="team__grid">
            <?php foreach ($teamHome as $member) {
                site_render_team_card($member);
            } ?>
        </div>
    </div>
</section>
