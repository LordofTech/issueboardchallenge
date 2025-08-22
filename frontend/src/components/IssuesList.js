// src/components/IssuesList.js
import React, { useEffect, useState } from "react";
import { fetchIssues } from "../services/api";

const IssuesList = () => {
  const [issues, setIssues] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const loadIssues = async () => {
      try {
        const data = await fetchIssues();
        setIssues(data.data || []); // Laravel pagination wraps results in "data"
      } catch (error) {
        console.error("Error fetching issues:", error);
      } finally {
        setLoading(false);
      }
    };

    loadIssues();
  }, []);

  if (loading) return <p>Loading issues...</p>;

  return (
    <div>
      <h2>Issues</h2>
      <ul>
        {issues.map((issue) => (
          <li key={issue.id}>
            <strong>{issue.title}</strong> - {issue.description} <br />
            <em>Status: {issue.status}, Priority: {issue.priority}</em>
          </li>
        ))}
      </ul>
    </div>
  );
};

export default IssuesList;
