// src/hooks/useIssues.js
import { useState, useEffect } from "react";
import api from "../services/api";

export default function useIssues() {
  const [issues, setIssues] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    async function fetchIssues() {
      try {
        setLoading(true);
        const response = await api.get("/issues"); // backend route: GET /api/v1/issues
        setIssues(response.data.data || response.data); // adjust if API returns differently
      } catch (err) {
        setError(err.message || "Failed to load issues");
      } finally {
        setLoading(false);
      }
    }

    fetchIssues();
  }, []);

  return { issues, loading, error };
}
