<?php

include '../modules/I18n.php';

echo "Testing English...\n\n";

$i18n = new I18n('en');

echo "login.username: " . $i18n->translate('login.username') . "\n";
echo "dashboard.top_proofers: " . $i18n->translate('dashboard.top_proofers') . "\n";
echo "footer.text: " . $i18n->translate('footer.text') . "\n";


echo "\nTesting faux Spanish...\n\n";

$i18n = new I18n('es');

echo "login.username: " . $i18n->translate('login.username') . "\n";
echo "dashboard.top_proofers: " . $i18n->translate('dashboard.top_proofers') . "\n";
echo "footer.text: " . $i18n->translate('footer.text') . "\n";


echo "\nTesting a key that doesn't exist...\n\n";

echo "supercalifragilisticexpialidocious: " . $i18n->translate('supercalifragilisticexpialidocious') . "\n";

?>
