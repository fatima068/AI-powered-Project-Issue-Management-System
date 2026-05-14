<?php
session_start();
include '../connect_db.php';
include '../auth_check.php';
require_page_access($conn, 'ai_eda_dashboard');
include '../assets/homeNavBar.php';
include '../ai_client.php';

$eda = ai_call('/api/eda');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>EDA Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <a href="ai_home.php" class="btn btn-link">&larr; AI Home</a>
    <h2 class="mb-4 text-primary">Exploratory Data Analysis</h2>
    <p class="text-muted">
        Insights from <code>activitylog</code>, <code>tasks</code>,
        and <code>statushistory</code> — plus AI service performance.
    </p>

    <?php echo ai_error_banner($eda); ?>

    <?php if (isset($eda['overall'])): $ov = $eda['overall']; ?>
        <div class="row g-3 mb-4">
            <?php foreach ([
                'developers' => 'Developers',
                'projects' => 'Projects',
                'tasks' => 'Total Tasks',
                'tasks_completed' => 'Completed',
                'tasks_overdue' => 'Overdue',
                'log_entries' => 'Log Entries',
            ] as $k => $label): ?>
                <div class="col-md-2">
                    <div class="card text-center p-3 shadow-sm bg-white">
                        <h6 class="text-muted mb-1"><?php echo $label; ?></h6>
                        <h3><?php echo (int)($ov[$k] ?? 0); ?></h3>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Response time metrics: NEW -->
    <?php if (!empty($eda['response_times'])): ?>
        <div class="card shadow-sm p-3 bg-white mb-4">
            <h5>AI Service Response Times</h5>
            <p class="small text-muted mb-2">
                Recent latency (last 500 calls per endpoint, in milliseconds).
                Helps detect slow endpoints or regressions.
            </p>
            <div class="table-responsive">
            <table class="table table-sm">
                <thead><tr>
                    <th>Endpoint</th><th>Calls</th>
                    <th>Avg ms</th><th>Min ms</th><th>Max ms</th>
                </tr></thead>
                <tbody>
                <?php foreach ($eda['response_times'] as $rt): ?>
                    <tr>
                        <td><code><?php echo h($rt['endpoint']); ?></code></td>
                        <td><?php echo (int)$rt['calls']; ?></td>
                        <td><?php echo h($rt['avg_ms']); ?></td>
                        <td><?php echo h($rt['min_ms']); ?></td>
                        <td><?php echo h($rt['max_ms']); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        </div>
    <?php endif; ?>

    <div class="row g-3">
        <div class="col-md-6">
            <div class="card shadow-sm p-3 bg-white h-100">
                <h5>Developer Productivity</h5>
                <?php if (!empty($eda['developer_productivity'])): ?>
                    <div class="table-responsive">
                    <table class="table table-sm">
                        <thead><tr>
                            <th>Developer</th><th>Completed</th>
                            <th>In Progress</th><th>Avg Days</th>
                        </tr></thead>
                        <tbody>
                        <?php foreach ($eda['developer_productivity'] as $d): ?>
                            <tr>
                                <td><?php echo h($d['name']); ?></td>
                                <td><?php echo (int)$d['completed']; ?></td>
                                <td><?php echo (int)$d['in_progress']; ?></td>
                                <td><?php echo h($d['avg_days']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted">No developer data.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow-sm p-3 bg-white h-100">
                <h5>Bottleneck Statuses</h5>
                <p class="small text-muted">
                    Average time tasks dwell in each status.
                </p>
                <?php if (!empty($eda['bottleneck_status'])): ?>
                    <?php foreach ($eda['bottleneck_status'] as $b):
                        $pct = min(100, ($b['avg_hours'] ?? 0) * 2); ?>
                        <div class="mb-2">
                            <div class="d-flex justify-content-between small">
                                <span><?php echo h($b['status_name']); ?></span>
                                <span><?php echo h($b['avg_hours']); ?>h avg</span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-info"
                                     style="width: <?php echo $pct; ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted">Need more statushistory data.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card shadow-sm p-3 bg-white h-100">
                <h5>Activity by Hour of Day</h5>
                <?php if (!empty($eda['busiest_hours'])):
                    $max = max(array_map(fn($r) => (int)$r['n'], $eda['busiest_hours']));
                    $max = max(1, $max); ?>
                    <div class="d-flex align-items-end" style="height:140px;gap:2px;">
                        <?php foreach ($eda['busiest_hours'] as $h):
                            $pct = ((int)$h['n'] / $max) * 100; ?>
                            <div class="d-flex flex-column align-items-center"
                                 style="flex:1" title="<?php echo (int)$h['n']; ?> events at <?php echo (int)$h['hour']; ?>:00">
                                <div class="bg-primary w-100"
                                     style="height: <?php echo $pct; ?>%"></div>
                                <small class="mt-1" style="font-size:10px">
                                    <?php echo (int)$h['hour']; ?>
                                </small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted">No activity yet.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm p-3 bg-white h-100">
                <h5>Top Actions</h5>
                <?php if (!empty($eda['top_actions'])): ?>
                    <ol>
                    <?php foreach ($eda['top_actions'] as $a): ?>
                        <li>
                            <?php echo h($a['action']); ?>
                            <span class="badge bg-secondary"><?php echo (int)$a['n']; ?></span>
                        </li>
                    <?php endforeach; ?>
                    </ol>
                <?php else: ?>
                    <p class="text-muted">No actions logged yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
</body>
</html>