import os
import json
from config import OPENAI_API_KEY, OPENAI_MODEL

try:
    from openai import OpenAI 
    _SDK_AVAILABLE = True
except ImportError:
    _SDK_AVAILABLE = False

def _api_key(): #to get api call from enviroment or fallback to the one in config
    return os.environ.get("OPENAI_API_KEY") or OPENAI_API_KEY

def _fallback_explanation(payload): #for explaination with llm (no api key or openai unavailable)
    rec = payload.get("recommended") or {} #get rec dev
    if not rec:
        infeasible = payload.get("infeasible") or [] #get rejected devs
        if infeasible: 
            return (f"No suitable developer was found by the constraint solver. All {len(infeasible)} candidate(s) were rejected because they are at maximum task capacity. Consider freeing up an existing assignment before running the recommender.")
        return ("No suitable developer was found. The project may have no developers assigned, or prerequisite tasks have not yet been completed.")

    name = rec.get("name", "the recommended developer") #get rec dev name
    score = rec.get("score", 0) #csp score
    active = rec.get("active_tasks", 0) 
    ranked = payload.get("ranked", []) #ranked dev list
    n_other = max(0, len(ranked) - 1) #count of remaining devs
    return (f"{name} is the strongest match for this task with a CSP score of {score}. They currently have {active} active task(s), giving them capacity to take on new work. The score combines workload (weighted 0.5), past completions at this priority level (0.25), and prior experience on this project (0.25). {n_other} other feasible candidate(s) were considered but ranked lower on the same scoring function.")


def explain_recommendation(payload):
    if not _SDK_AVAILABLE:
        return {"explanation": _fallback_explanation(payload), "source": "template (openai SDK not installed)"}
    if not _api_key():
        return {"explanation": _fallback_explanation(payload), "source": "template (set OPENAI_API_KEY for LLM-powered output)"}

    try:
        client = OpenAI(api_key=_api_key()) #create an openai client
        summary = json.dumps({ #convert rec data to json
            "recommended": payload.get("recommended"),
            "ranked_top3": (payload.get("ranked") or [])[:3],
            "infeasible_count": len(payload.get("infeasible") or []),
        }, default=str)

        prompt = ("You are a project management assistant. Given this AI recommendation result for assigning a task to a developer, write a one-paragraph (3-4 sentences) justification a manager could share with their team. Be specific about why this developer was chosen. Reference the score, workload, and any priority/project history present. Do NOT invent facts beyond what's in the data.\n\n Recommendation data:\n" + summary)

        resp = client.chat.completions.create(model=OPENAI_MODEL, messages=[{"role": "user", "content": prompt}], temperature=0.3, max_tokens=200) #send request to openai
        return {"explanation": resp.choices[0].message.content.strip(),"source": f"OpenAI ({OPENAI_MODEL})"}
    except Exception as e: #handle api error
        return {"explanation": _fallback_explanation(payload), "source": f"template (LLM error: {str(e)[:120]})"}