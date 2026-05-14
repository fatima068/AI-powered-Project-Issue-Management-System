from contextlib import contextmanager
import mysql.connector
from config import DB_CONFIG

@contextmanager
def get_conn(): #get a live Mysql connection and guarantee it is closed
    conn = mysql.connector.connect(**DB_CONFIG)
    try:
        yield conn
    finally:
        conn.close()

def query(conn, sql, params=None):
    cur = conn.cursor(dictionary=True)
    cur.execute(sql, params or ())
    rows = cur.fetchall()
    cur.close()
    return rows

def execute(conn, sql, params=None):
    cur = conn.cursor()
    cur.execute(sql, params or ())
    last_id = cur.lastrowid
    conn.commit()
    cur.close()
    return last_id
