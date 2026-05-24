<?php

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($id) {
    header('Location: update.php?ID=' . $id);
} else {
    header('Location: index.php');
}

exit();
