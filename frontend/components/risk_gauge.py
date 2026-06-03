"""Reusable Plotly gauge for displaying credit default risk."""

from __future__ import annotations

import plotly.graph_objects as go


def risk_label(risk_score: float) -> str:
    """Return readable risk label from a 0-1 risk score."""
    score = max(0.0, min(float(risk_score), 1.0))
    if score < 0.2:
        return "LOW RISK"
    if score < 0.4:
        return "MEDIUM RISK"
    if score < 0.7:
        return "HIGH RISK"
    return "VERY HIGH RISK"


def create_risk_gauge(risk_score: float) -> go.Figure:
    """Create a simple gauge chart for credit risk visualization."""
    score = max(0.0, min(float(risk_score), 1.0))
    score_pct = score * 100

    fig = go.Figure(
        go.Indicator(
            mode="gauge+number",
            value=score_pct,
            number={"suffix": "%", "font": {"size": 34}},
            title={"text": risk_label(score), "font": {"size": 18}},
            gauge={
                "axis": {"range": [0, 100], "tickwidth": 1, "tickcolor": "#475569"},
                "bar": {"color": "#0f172a", "thickness": 0.18},
                "bgcolor": "white",
                "borderwidth": 1,
                "bordercolor": "#e2e8f0",
                "steps": [
                    {"range": [0, 20], "color": "#dcfce7"},
                    {"range": [20, 40], "color": "#fef9c3"},
                    {"range": [40, 70], "color": "#fed7aa"},
                    {"range": [70, 100], "color": "#fecaca"},
                ],
                "threshold": {
                    "line": {"color": "#ef4444", "width": 4},
                    "thickness": 0.75,
                    "value": score_pct,
                },
            },
        )
    )

    fig.update_layout(
        height=300,
        margin={"l": 20, "r": 20, "t": 55, "b": 10},
        paper_bgcolor="rgba(0,0,0,0)",
        font={"family": "Inter, Arial, sans-serif", "color": "#0f172a"},
    )
    return fig
