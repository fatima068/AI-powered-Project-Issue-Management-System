-- =====================================================================
-- AI Extension to Project Issue Tracking System
-- Run this AFTER your existing database has been imported.
-- The default DB here is project_issue_tracking2 (this user's name).
-- If your DB is named differently, edit the USE line below.
-- =====================================================================

USE project_issue_tracking2;

-- ---------- New pages for the AI module ----------
INSERT INTO pages (page_name, page_path, description) VALUES
('ai_home',                 'manager/ai_home.php',                 'AI Assistant Hub'),
('ai_recommend_developer',  'manager/ai_recommend_developer.php',  'AI-based developer recommendation (CSP)'),
('ai_predict_delay',        'manager/ai_predict_delay.php',        'ML-based task delay prediction'),
('ai_schedule_optimizer',   'manager/ai_schedule_optimizer.php',   'A*/Hill-Climbing schedule optimizer'),
('ai_eda_dashboard',        'manager/ai_eda_dashboard.php',        'Exploratory Data Analysis dashboard');

-- ---------- Grant Admin (1) and Manager (2) access ----------
INSERT INTO privileges (role_id, page_id, can_access)
SELECT 1, page_id, 1 FROM pages
WHERE page_name IN ('ai_home','ai_recommend_developer','ai_predict_delay',
                    'ai_schedule_optimizer','ai_eda_dashboard');

INSERT INTO privileges (role_id, page_id, can_access)
SELECT 2, page_id, 1 FROM pages
WHERE page_name IN ('ai_home','ai_recommend_developer','ai_predict_delay',
                    'ai_schedule_optimizer','ai_eda_dashboard');

-- Developer/Stakeholder denied by default but listed in Manage Privileges
INSERT INTO privileges (role_id, page_id, can_access)
SELECT 3, page_id, 0 FROM pages
WHERE page_name IN ('ai_home','ai_recommend_developer','ai_predict_delay',
                    'ai_schedule_optimizer','ai_eda_dashboard');

INSERT INTO privileges (role_id, page_id, can_access)
SELECT 4, page_id, 0 FROM pages
WHERE page_name IN ('ai_home','ai_recommend_developer','ai_predict_delay',
                    'ai_schedule_optimizer','ai_eda_dashboard');

-- ---------- Audit tables ----------
CREATE TABLE IF NOT EXISTS ai_predictions (
    prediction_id    INT AUTO_INCREMENT PRIMARY KEY,
    task_id          INT          NOT NULL,
    predicted_days   FLOAT        NOT NULL,
    delay_risk       VARCHAR(20)  NOT NULL,
    will_miss_deadline TINYINT(1) NOT NULL,
    model_version    VARCHAR(20)  NOT NULL DEFAULT 'v1',
    created_at       TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_aipred_task FOREIGN KEY (task_id)
        REFERENCES tasks(task_id) ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE INDEX idx_aipred_task ON ai_predictions(task_id);

CREATE TABLE IF NOT EXISTS ai_recommendations (
    recommendation_id INT AUTO_INCREMENT PRIMARY KEY,
    project_id        INT          NOT NULL,
    task_title        VARCHAR(200) NOT NULL,
    priority_id       INT,
    recommended_user  INT,
    score             FLOAT,
    reason            VARCHAR(255),
    created_at        TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_airec_project  FOREIGN KEY (project_id)
        REFERENCES projects(project_id) ON DELETE CASCADE,
    CONSTRAINT fk_airec_user     FOREIGN KEY (recommended_user)
        REFERENCES users(user_id) ON DELETE SET NULL,
    CONSTRAINT fk_airec_priority FOREIGN KEY (priority_id)
        REFERENCES priority(priority_id)
) ENGINE=InnoDB;
