import React, { useState } from "react";
import axios from "axios";

export default function CreateIssue() {
    const [title, setTitle] = useState("");
    const [description, setDescription] = useState("");
    const [status, setStatus] = useState("open");
    const [priority, setPriority] = useState("low");
    const [error, setError] = useState(null);
    const [loading, setLoading] = useState(false);

    const handleSubmit = async (e) => {
        e.preventDefault();
        setLoading(true);
        setError(null);

        try {
            const res = await axios.post("/api/issues", {
                title,
                description,
                status,
                priority,
            });

            // Clear form on success
            setTitle("");
            setDescription("");
            setStatus("open");
            setPriority("low");

            setLoading(false);
        } catch (err) {
            if (err.response && err.response.data && err.response.data.errors) {
                setError(Object.values(err.response.data.errors).flat().join(" "));
            } else {
                setError("Something went wrong.");
            }
            setLoading(false);
        }
    };

    return (
        <div className="p-6 mb-6 border rounded">
            <h2 className="text-xl font-bold mb-4">Create New Issue</h2>
            {error && <div className="text-red-600 mb-2">{error}</div>}
            <form onSubmit={handleSubmit}>
                <div className="mb-2">
                    <input
                        type="text"
                        placeholder="Title"
                        value={title}
                        onChange={(e) => setTitle(e.target.value)}
                        className="border p-2 w-full"
                        required
                    />
                </div>
                <div className="mb-2">
                    <textarea
                        placeholder="Description"
                        value={description}
                        onChange={(e) => setDescription(e.target.value)}
                        className="border p-2 w-full"
                        required
                    ></textarea>
                </div>
                <div className="mb-2">
                    <select value={status} onChange={(e) => setStatus(e.target.value)} className="border p-2 w-full">
                        <option value="open">Open</option>
                        <option value="in_progress">In Progress</option>
                        <option value="closed">Closed</option>
                    </select>
                </div>
                <div className="mb-2">
                    <select value={priority} onChange={(e) => setPriority(e.target.value)} className="border p-2 w-full">
                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                    </select>
                </div>
                <button type="submit" disabled={loading} className="bg-blue-500 text-white px-4 py-2 rounded">
                    {loading ? "Creating..." : "Create Issue"}
                </button>
            </form>
        </div>
    );
}
