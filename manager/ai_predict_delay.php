<?php
session_start();
include '../connect_db.php';
include '../auth_check.php';
require_page_access($conn, 'ai_predict_delay');
include '../assets/homeNavBar.php';
include '../ai_client.php';

$train_result = null;
$batch = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['train'])) {
        $train_result = ai_call('/api/train_model', []);
    } elseif (isset($_POST['predict_all'])) {
        $batch = ai_call('/api/predict_delay_all');
        if (is_array($batch)) {
            $stmt = $conn->prepare("
                INSERT INTO ai_predictions
                  (task_id, predicted_days, delay_risk, will_miss_deadline)
                VALUES (?,?,?,?)
            ");
            foreach ($batch as $p) {
                if (!isset($p['task_id'])) continue;
                $tid = (int)$p['task_id']; $pd = (float)$p['predicted_days'];
                $risk = $p['delay_risk'];   $mis = !empty($p['will_miss_deadline']) ? 1 : 0;
                $stmt->bind_param("idsi", $tid, $pd, $risk, $mis);
                $stmt->execute();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Task Delay Prediction</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <a href="ai_home.php" class="btn btn-link">&larr; AI Home</a>
    <h2 class="mb-4 text-primary">Task Delay Prediction</h2>
    <p class="text-muted">
        Random Forest regressor trained on statushistory predicts the
        expected completion time and delay risk for every open task.
    </p>

    <div class="d-flex gap-2 mb-3">
        <form method="POST"><button name="train" class="btn btn-outline-secondary">
            Train / Retrain Model
        </button></form>
        <form method="POST"><button name="predict_all" class="btn btn-primary">
            Predict for All Open Tasks
        </button></form>
    </div>

    <?php echo ai_error_banner($train_result); ?>
    <?php echo ai_error_banner($batch); ?>

    <?php if ($train_result && empty($train_result['error'])): ?>
        <div class="card mb-4 p-3 shadow-sm bg-white">
            <h5>Training Result</h5>
            <?php if (!empty($train_result['trained'])): ?>
                <ul class="mb-0">
                    <li>Samples used: <?php echo (int)$train_result['samples']; ?></li>
                    <li>MAE (days): <?php echo h($train_result['mae']); ?></li>
                    <li>R&sup2;: <?php echo h($train_result['r2']); ?></li>
                </ul>
                <h6 class="mt-3">Feature Importance</h6>
                <table class="table table-sm">
                <?php foreach ($train_result['feature_importance'] as $feat => $imp): ?>
                    <tr>
                        <td style="width:40%"><?php echo h($feat); ?></td>
                        <td>
                            <div class="progress">
                                <div class="progress-bar" role="progressbar"
                                     style="width: <?php echo ($imp*100); ?>%">
                                     <?php echo h($imp); ?>
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </table>
            <?php else: ?>
                <p class="text-warning mb-0">
                    <?php echo h($train_result['message'] ?? 'Training skipped.'); ?>
                </p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if (is_array($batch) && !isset($batch['error'])): ?>
        <h5>Predictions</h5>
        <?php if (count($batch) === 0): ?>
            <p class="text-muted">No open tasks to predict.</p>
        <?php else: ?>
            <?php
            $ids = array_column($batch, 'task_id');
            $title_map = [];
            if ($ids) {
                $in = implode(',', array_map('intval', $ids));
                $r = mysqli_query($conn, "SELECT task_id, title FROM tasks WHERE task_id IN ($in)");
                while ($row = mysqli_fetch_assoc($r)) $title_map[(int)$row['task_id']] = $row['title'];
            }
            ?>
            <div class="table-responsive">
            <table class="table table-striped bg-white">
                <thead class="table-dark"><tr>
                    <th>Task ID</th><th>Title</th>
                    <th>Predicted Days</th><th>Days To Due</th>
                    <th>Risk</th><th>On Time?</th>
                </tr></thead>
                <tbody>
                <?php foreach ($batch as $p): ?>
                    <?php $badge = 'secondary';
                          if ($p['delay_risk'] === 'Low')    $badge = 'success';
                          elseif ($p['delay_risk'] === 'Medium') $badge = 'warning';
                          elseif ($p['delay_risk'] === 'High')   $badge = 'danger';
                    ?>
                    <tr>
                        <td><?php echo (int)$p['task_id']; ?></td>
                        <td><?php echo h($title_map[(int)$p['task_id']] ?? ''); ?></td>
                        <td><?php echo h($p['predicted_days']); ?></td>
                        <td><?php echo h($p['days_to_due'] ?? '—'); ?></td>
                        <td><span class="badge bg-<?php echo $badge; ?>">
                            <?php echo h($p['delay_risk']); ?></span></td>
                        <td><?php echo empty($p['will_miss_deadline'])
                                    ? '<span class="text-success">Yes</span>'
                                    : '<span class="text-danger">No</span>'; ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
</body>
</html>
