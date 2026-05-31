# Converge
A secure, role-based collaboration platform where students can propose, discover, and join cross-department research projects vetted by faculty administrators.

## AI Assistant Integration
The dashboard now includes a built-in AI Research Assistant powered by Hugging Face inference with a free API key. It can answer project idea questions, collaboration advice, and platform usage help.

### Setup
1. Copy `backend/.env.example` to `backend/.env`.
2. Add your free Hugging Face API key to `HF_API_KEY`.
3. Optionally set `HF_MODEL` if you want a different free model.
4. OpenAI is still supported as a fallback when `OPENAI_API_KEY` is provided.
5. Use the dashboard as a logged-in user and ask the assistant from the sidebar.
