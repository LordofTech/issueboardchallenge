import React, { useEffect, useState } from 'react';
import axios from 'axios';

const IssuesList = () => {
    // State for storing issues
    const [issues, setIssues] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    // Fetch initial issues from API
    const fetchIssues = async () => {
        try {
            const response = await axios.get('/api/issues?page=1&per_page=15');
            setIssues(response.data.data || response.data); // depending on pagination response
        } catch (err) {
            setError('Failed to load issues');
            console.error(err);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchIssues();

        // Subscribe to Laravel Reverb channel for real-time updates
        if (window.Echo) {
            window.Echo.channel('issues')
                .listen('IssueCreated', (e) => {
                    console.log("New issue created:", e.issue);
                    setIssues((prev) => [e.issue, ...prev]); // prepend new issue
                })
                .listen('IssueUpdated', (e) => {
                    console.log("Issue updated:", e.issue);
                    setIssues((prev) =>
                        prev.map((i) => (i.id === e.issue.id ? e.issue : i))
                    );
                });
        }

        // Optional: cleanup on unmount
        return () => {
            if (window.Echo) {
                window.Echo.leaveChannel('issues');
            }
        };
    }, []);

    // Render
    if (loading) return <p>Loading issues...</p>;
    if (error) return <p>{error}</p>;

    return (
        <div className="p-6">
            <h2 className="text-xl font-bold mb-4">Issues Board</h2>
            <table className="min-w-full bg-white border">
                <thead>
                    <tr>
                        <th className="border px-4 py-2">ID</th>
                        <th className="border px-4 py-2">Title</th>
                        <th className="border px-4 py-2">Description</th>
                        <th className="border px-4 py-2">Status</th>
                        <th className="border px-4 py-2">Priority</th>
                        <th className="border px-4 py-2">Updated At</th>
                    </tr>
                </thead>
                <tbody>
                    {issues.map((issue) => (
                        <tr key={issue.id}>
                            <td className="border px-4 py-2">{issue.id}</td>
                            <td className="border px-4 py-2">{issue.title}</td>
                            <td className="border px-4 py-2">{issue.description}</td>
                            <td className="border px-4 py-2">{issue.status}</td>
                            <td className="border px-4 py-2">{issue.priority}</td>
                            <td className="border px-4 py-2">{issue.updated_at}</td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
};

export default IssuesList;
