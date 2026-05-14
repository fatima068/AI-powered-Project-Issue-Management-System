# Project Issue Tracking System with AI Extension

A web-based project management system built with PHP and MySQL, extended with an AI module for intelligent task assignment, delay prediction, and schedule optimisation.

**Team:** Syeda Fatima Waseem (24k-0924), Syed Ikrash Ahmed (24k-0998)  
**Courses:** Database Systems (CS-2005), Artificial Intelligence (AL-2002), Software Design and Analysis (CS-3004)

---

## Overview

The base system allows teams to manage projects, tasks, and issues with role-based access control. Admins manage users and permissions. Managers create and assign tasks. Developers track their work. Stakeholders view progress reports.

The AI extension adds a Python Flask service that runs alongside the PHP application and provides four AI-powered features accessible to managers: developer recommendation, task delay prediction, schedule optimisation, and an analytics dashboard. An n8n automation workflow sends daily email alerts for high-risk tasks.

---

## Screenshots

**Login Page**

<img width="960" height="437" alt="image" src="https://github.com/user-attachments/assets/57076ae9-b4d9-4ff5-9897-ecefffe24d24" />

---

**Admin Dashboard**

<img width="960" height="440" alt="image" src="https://github.com/user-attachments/assets/2f0d2ce2-e88b-4a0c-b00f-61024abbe892" />

---

**Manager Dashboard**

<img width="960" height="439" alt="image" src="https://github.com/user-attachments/assets/cfa2ef25-f7f3-4fa8-a7f2-1df150a4c366" />

---

**Manage Tasks**

<img width="960" height="439" alt="image" src="https://github.com/user-attachments/assets/95a082de-7b81-47f6-8cab-9df28895ec34" />

---

**Manage Issues**

<img width="960" height="434" alt="image" src="https://github.com/user-attachments/assets/cb99797e-c336-450f-b7d9-b3fd662f2a3d" />

---

**Project Reports**

<img width="960" height="436" alt="image" src="https://github.com/user-attachments/assets/9af5b9bf-d480-48d1-9205-30b5e8bd2ce2" />

---

**Developer Dashboard**

<img width="960" height="441" alt="image" src="https://github.com/user-attachments/assets/408d60be-19e9-427c-9ab3-5137eef549f0" />

---

**Manage Users (Admin)**

<img width="959" height="434" alt="image" src="https://github.com/user-attachments/assets/f17eb9d7-585b-4546-9700-09f00b514909" />

---

**Manage Privileges (Admin)**

<img width="960" height="436" alt="image" src="https://github.com/user-attachments/assets/144ee384-64f7-4295-9033-eaced160e2ea" />

---

**AI Home Page**

<img width="960" height="437" alt="image" src="https://github.com/user-attachments/assets/8e47667e-d3fa-488d-bde2-efec40c09511" />

---

**Developer Recommendation — CSP engine with prerequisite task selection**

<img width="959" height="441" alt="image" src="https://github.com/user-attachments/assets/459eafe0-8d6a-497e-afc5-4eecd511b8f8" />
<img width="960" height="230" alt="image" src="https://github.com/user-attachments/assets/0784bd2f-9a3e-4d51-a116-2b9b0894f8fe" />

---

**Task Delay Prediction — Random Forest model results and risk table**

<img width="960" height="438" alt="image" src="https://github.com/user-attachments/assets/555f3b1c-716e-4342-8e60-2861cc6f8917" />
<img width="960" height="434" alt="image" src="https://github.com/user-attachments/assets/69e08304-a202-4a6b-9a84-64c1585b6e44" />

---

**Schedule Optimizer — BFS levels, A\* ordering, Hill Climbing assignments**

<img width="960" height="440" alt="image" src="https://github.com/user-attachments/assets/15b3cd86-5b1f-41a2-ace7-07d812c74dc7" />
<img width="960" height="439" alt="image" src="https://github.com/user-attachments/assets/03fbc36c-8afe-4ec6-aa92-b885f64d1e16" />

---

**EDA Dashboard — productivity, bottlenecks, and response time metrics**

<img width="960" height="439" alt="image" src="https://github.com/user-attachments/assets/88cac264-2219-4766-ba2c-40db7fc53b6a" />
<img width="960" height="437" alt="image" src="https://github.com/user-attachments/assets/ff3af22c-33e7-4ccf-8f63-e094692c2f99" />

---

## Features

### Base System

- Role-based access control with four roles: Admin, Manager, Developer, Stakeholder
- Project creation and team membership management
- Task and issue creation, assignment, and status tracking
- Comments on tasks and issues
- Status history and activity logging
- Project reports and overdue task views
- Admin privilege management per role per page
- Stored procedures for atomic status updates

### AI Extension

- **Developer Recommendation** — Constraint Satisfaction Problem engine that enforces hard constraints (capacity, membership, dependencies) and ranks feasible developers by a soft scoring function
- **Delay Prediction** — Random Forest Regressor trained on historical completion data, predicts days to complete and flags tasks as Low, Medium, or High risk
- **Schedule Optimizer** — three-stage pipeline: BFS resolves dependencies, A\* finds the optimal task order, Hill Climbing balances workload across developers
- **EDA Dashboard** — developer productivity, workflow bottlenecks, busiest hours, and AI service response-time metrics
- **AI Explanation** — OpenAI GPT-4o-mini generates a plain-English justification for each recommendation
- **n8n Automation** — daily email alerts to the project manager for every high-risk task

---

## Tech Stack

| Layer | Technology |
|---|---|
| Frontend | PHP 8, Bootstrap 5, HTML, CSS, JavaScript |
| Database | MySQL via XAMPP |
| AI Service | Python 3.12, Flask |
| ML Library | scikit-learn (Random Forest) |
| LLM | OpenAI gpt-4o-mini (optional) |

---

## Database

The system uses 13 core tables, 7 views, and 4 stored procedures.

**Core tables:** roles, users, status, priority, projects, projectmembers, tasks, issues, comments, statushistory, activitylog, pages, privileges

**Views:** v\_project\_summary, v\_task\_details, v\_issue\_details, v\_user\_activity\_summary, v\_overdue\_tasks, v\_role\_privileges, v\_comments\_full

**Stored procedures:** sp\_update\_task\_status, sp\_update\_issue\_status, sp\_add\_task\_comment, sp\_add\_issue\_comment

The AI extension adds two audit tables: ai\_predictions and ai\_recommendations.

---

## How to Run

### Requirements

- XAMPP (Apache + MySQL + PHP 8)
- Python 3.10, 3.11, or 3.12 — do not use 3.13

---

### Step 1 — Database

1. Open phpMyAdmin at `http://localhost/phpmyadmin`
2. Import `database.sql` to create the base schema
3. Import `sql/ai_extension.sql` into the same database

---

### Step 2 — PHP Files

Copy the files into your existing htdocs project:

---

### Step 3 — Python AI Service

```bash
cd ai_module
py -m pip install -r requirements.txt
py train_model.py
py app.py
```

Flask starts on `http://127.0.0.1:5001`. Keep this terminal open while using the app. Confirm it is running by visiting that URL — you should see `{"status": "ok"}`.

---

### Every Session

1. Start XAMPP — Apache and MySQL
2. Open a terminal and run `py app.py` inside `ai_module/`
3. Open your browser and navigate to the project

---

## Login Credentials

After running the seeder:

| Role | Email | Password |
|---|---|---|
| Admin | admin@example.com | Admin@123 |
| Manager / Developer / Stakeholder | firstname@test.com (e.g. babar@test.com, virat@test.com) | Password@123 |

Check the `users` table in phpMyAdmin to find which cricketer has which role (`role_id`: 1 = Admin, 2 = Manager, 3 = Developer, 4 = Stakeholder).

---

## References

- Russell, S. J., & Norvig, P. Artificial Intelligence: A Modern Approach (4th ed.)
- Scikit-learn Documentation: https://scikit-learn.org
- Flask Documentation: https://flask.palletsprojects.com  
