// src/services/api.js
import axios from "axios";

const api = axios.create({
  baseURL: "http://127.0.0.1:8000/api", // Laravel API URL
  headers: {
    "Content-Type": "application/json",
    Accept: "application/json",
  },
});

// Example function to get issues
export const fetchIssues = async () => {
  try {
    const response = await api.get("/issues");
    return response.data;
  } catch (error) {
    console.error("Error fetching issues:", error);
    throw error;
  }
};

export default api;
