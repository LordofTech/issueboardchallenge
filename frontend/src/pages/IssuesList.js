import React, { useEffect, useState } from "react";
import api from "../services/api";       // axios instance
import echo from "../services/websocket"; // Laravel Reverb (Echo) instance

export default function IssuesList() {
  const [issues, setIssues] = useState([]);

  useEffect(() => {
    // Fetch initial issues from backend
    api.get("/issues")
      .then((res) => {
        // Laravel returns { status, data: { ...pagination } }
        const data = res.data.data || res.data; 
        setIssues(data);
      })
      .catch((err) => console.error("Error fetching issues:", err));

    // Subscribe to Reverb real-time channel
    const channel = echo.channel("issues");

    channel.listen("IssueCreated", (event) => {
      setIssues((prev) => [event.issue, ...prev]);
    });

    channel.listen("IssueUpdated", (event) => {
      setIssues((prev) =>
        prev.map((issue) =>
          issue.id === event.issue.id ? event.issue : issue
        )
      );
    });

    channel.listen("IssueDeleted", (event) => {
      setIssues((prev) => prev.filter((issue) => issue.id !== event.issueId));
    });

    // Cleanup when component unmounts
    return () => {
      echo.leave("issues");
    };
  }, []);

  return (
    <div className="p-4">
      <h1 className="text-xl font-bold mb-4">Issues</h1>
      <ul className="space-y-2">
        {issues.map((issue) => (
          <li key={issue.id} className="p-4 bg-white rounded shadow">
            <h2 className="font-semibold">{issue.title}</h2>
            <p className="text-gray-600">{issue.description}</p>
            <div className="text-sm text-gray-500">
              Status: {issue.status} | Priority: {issue.priority}
            </div>
          </li>
        ))}
      </ul>
    </div>
  );
}
