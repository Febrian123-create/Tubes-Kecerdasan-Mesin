#!/bin/bash

set -e

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$ROOT_DIR"

export PYTHONPATH="$ROOT_DIR:${PYTHONPATH}"

cleanup() {
  echo "Stopping services..."
  jobs -p | xargs -r kill
}
trap cleanup EXIT

echo "Starting CreditAI services..."

uvicorn agents.agent1_scoring:app --host 0.0.0.0 --port 8001 &
uvicorn agents.agent2_decision:app --host 0.0.0.0 --port 8002 &
uvicorn agents.agent3_insight:app --host 0.0.0.0 --port 8003 &
uvicorn api.orchestrator:app --host 0.0.0.0 --port 8000 &

sleep 3

echo "Opening Streamlit frontend at http://localhost:8501"
streamlit run frontend/app.py --server.port 8501
