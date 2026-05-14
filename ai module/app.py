from flask import Flask, request, jsonify
from flask_cors import CORS

import recommender
import scheduler
import predictor
import eda
import llm
from metrics import timed
from config import FLASK_HOST, FLASK_PORT, DEBUG

app = Flask(__name__) #create flask app object
CORS(app) #enable cross origin resource sharing for all routes

@app.get("/api/health") #create a GET endpoint
@timed("health") #to measure exec time of this endpoint
def health():
    return jsonify({"status": "ok", "service": "AI Task Manager"}) #checks if backend server is running

#1 CSP developer recommendation
@app.post("/api/recommend_developer") #post endpoint for recommending dev
@timed("recommend_developer")
def api_recommend_developer():
    data = request.get_json(force=True, silent=True) or {} #avoid crash on error
    try:
        project_id  = int(data.get("project_id"))
        priority_id = int(data.get("priority_id"))
    except (TypeError, ValueError):
        return jsonify({"error": "project_id and priority_id are required ints"}), 400

    prereqs = data.get("prerequisite_task_ids") or [] 

    try:
        prereqs = [int(p) for p in prereqs] 
    except (TypeError, ValueError):
        prereqs = []

    result = recommender.recommend_developer(project_id, priority_id, prereqs) #call ai recommender module
    return jsonify(result) #return rec as json

#2 Task delay prediction
@app.post("/api/predict_delay") #post endpoint for predicting delay risk
@timed("predict_delay")
def api_predict_delay():
    data = request.get_json(force=True, silent=True) or {}
    try:
        task_id = int(data.get("task_id"))
    except (TypeError, ValueError):
        return jsonify({"error": "task_id is required int"}), 400
    return jsonify(predictor.predict_delay(task_id)) #sends task ids to ML predictor module

@app.get("/api/predict_delay_all") 
@timed("predict_delay_all")
def api_predict_delay_all(): #predict delays for all open tasks
    return jsonify(predictor.predict_all_open()) 

@app.post("/api/train_model") #retrain the model
@timed("train_model")
def api_train_model():
    return jsonify(predictor.train_and_save())

#3 Schedule optimiser A*, hill climb 
@app.post("/api/schedule_project") 
@timed("schedule_project")
def api_schedule_project():
    data = request.get_json(force=True, silent=True) or {}
    try:
        project_id = int(data.get("project_id"))
    except (TypeError, ValueError):
        return jsonify({"error": "project_id is required int"}), 400

    deps = data.get("dependencies") #reads dependency graph from frontend
    if isinstance(deps, dict):
        try:
            deps = {int(k): [int(x) for x in v] for k, v in deps.items()} #normalize json into int
        except (TypeError, ValueError):
            deps = None
    return jsonify(scheduler.schedule_project(project_id, dependencies=deps))

#4 EDA 
@app.get("/api/eda")
@timed("eda")
def api_eda():
    return jsonify(eda.run_eda())

#5 high-risk tasks enriched with manager email
@app.get("/api/high_risk_tasks")
@timed("high_risk_tasks")
def api_high_risk_tasks():
    return jsonify(predictor.fetch_high_risk_with_manager())

#6 LLM explanation 
@app.post("/api/explain")
@timed("explain")
def api_explain():
    payload = request.get_json(force=True, silent=True) or {}
    return jsonify(llm.explain_recommendation(payload))

if __name__ == "__main__": #starts flask development server
    print(f"[AI] Flask running on http://{FLASK_HOST}:{FLASK_PORT}")
    print("[AI] Endpoints:")
    for rule in app.url_map.iter_rules():
        methods = sorted(rule.methods - {"HEAD", "OPTIONS"})
        if rule.endpoint == "static":
            continue
        print(f"     {','.join(methods):8} {rule.rule}")
    app.run(host=FLASK_HOST, port=FLASK_PORT, debug=DEBUG)
