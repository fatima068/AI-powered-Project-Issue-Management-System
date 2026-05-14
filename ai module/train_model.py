from predictor import train_and_save

if __name__ == "__main__":
    print("[AI] Training delay predictor...")
    result = train_and_save()
    for k, v in result.items():
        print(f"  {k}: {v}")
    if not result.get("trained"):
        print("\nHint: run seed_database.php (in your htdocs project root) first to seed the database with realistic task history.")
