<?php

require_once __DIR__ . '/../../../../core/helpers.php';
require_once __DIR__ . '/../../../../core/DB.php';
require_once(__DIR__ . '/../../../../core/is_user.php');
CheckLogin();
CheckAdmin();
$project_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Lấy thông tin dự án hiện tại
$project = $NNL->get_row("SELECT * FROM projects WHERE id = $project_id");

if (!$project) {
    header('Location: /admin/projects');
    exit();
}
$del = $NNL->remove("projects", "`id`='" . $project_id . "'");
if($del){
        header("Location: /admin/projects?success=1");
        exit();
    }
    else{
         header("Location: /admin/projects?error=1");
        exit();
    }
?>