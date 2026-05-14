import os
DB_CONFIG = {
    "host": os.environ.get("DB_HOST", "127.0.0.1"),
    "port": int(os.environ.get("DB_PORT", 3306)),
    "user": os.environ.get("DB_USER", "root"),
    "password": os.environ.get("DB_PASSWORD", ""),
    "database": os.environ.get("DB_NAME", "project_issue_tracking2"),
}

FLASK_HOST = "127.0.0.1" #localhost addr
FLASK_PORT = 5001 #flask port
DEBUG = True

OPENAI_API_KEY = os.environ.get("OPENAI_API_KEY", "") #read openai api key from environment
OPENAI_MODEL = "gpt-4o-mini"  

BASE_DIR = os.path.dirname(os.path.abspath(__file__)) #get current dir
MODEL_DIR = os.path.join(BASE_DIR, "models")
MODEL_PATH = os.path.join(MODEL_DIR, "delay_predictor.pkl")

STATUS_PENDING = 1
STATUS_IN_PROGRESS = 2
STATUS_COMPLETED = 3
STATUS_OVERDUE = 4

ROLE_ADMIN = 1
ROLE_MANAGER = 2
ROLE_DEVELOPER = 3
ROLE_STAKEHOLDER = 4

MAX_ACTIVE_TASKS_PER_DEV = 5
HILL_CLIMB_MAX_ITER = 200
