# 🍕 Datapizza-AI PHP Examples
**The smallest AI learning framework in the world.**

This folder is your **LLM 101 journey** — a hands-on, line-by-line exploration of how large language models actually work *under the hood*, built entirely in **pure PHP**.  
Every script is **commented as a tutorial**, not as a library.  
You’re meant to *read* the code as much as *run* it.

---

## 🎯 Philosophy

Modern AI often looks like magic.  
These examples strip away every abstraction so you can watch intelligence form through **plain code and logic**.

- **No dependencies**  
- **No frameworks**  
- **No hidden layers**

Just loops, arrays, and a network connection — running on a **Raspberry Pi 1 Model B (2011)**.

The goal: show how reasoning, memory, and retrieval emerge from minimal ingredients.

---

## 🧩 The Core Pipeline

This is the *entire engine* you’ll see implemented across all files:  

```
🧠  Pipeline
───────────────────────────────
1️⃣  Create embeddings
2️⃣  Retrieve relevant context
3️⃣  Prompt the LLM
4️⃣  Print the answer
───────────────────────────────
```

No databases. No containers.  
You’ll see every variable, every loop, every token.  
A front-row seat inside the brain of your AI.

---

## 📚 The Learning Path

Each directory represents one level of abstraction in AI development — follow them in order:

### **Level 1 – Getting Started (5 min)**
📁 `01_getting_started/`  
Make your first call to an LLM and print the raw response.

Learn:
- how API calls are formed  
- how tokens become words  
- how PHP can talk directly to an LLM endpoint  

### **Level 2 – Agents (30 min)**
📁 `02_agents/`  
From simple calls to *thinking entities*.  
You’ll build a reasoning loop (ReAct) that lets AI plan, use tools, and remember.

Learn:
- ReAct = Reason → Act → Observe  
- how tools like `calculator` and `date` integrate  
- how context memory works across turns  

### **Level 3 – RAG Systems (1 h)**
📁 `03_rag/`  
Teach your AI to read documents.  
It will embed, search, and answer questions from your own text files.

Learn:
- what embeddings really are  
- how similarity search retrieves meaning  
- how context injection guides generation  

### **Level 4 – Advanced Patterns (1–2 h)**
📁 `04_advanced/`  
Orchestrate tasks, chain tools, and build pipelines.  
You’ll connect everything you learned into DAG workflows.

Learn:
- Directed Acyclic Graphs (DAGs)  
- multi-step reasoning  
- large-scale ingestion and batch processing  

---

## 🛠️ Requirements

- PHP 7 or higher (8 recommended)  
- Internet connection  
- `OPENAI_API_KEY` (from [OpenAI Dashboard](https://platform.openai.com/api-keys))  
- Any machine — works flawlessly even on **512 MB RAM**

---

## ⚙️ .env Setup

```env
OPENAI_API_KEY=your_api_key_here
REQUEST_TIMEOUT=30
MAX_CONCURRENT_REQUESTS=1
CACHE_ENABLED=true
```

> This file contains no secrets — it’s safe to commit.

---

## 💡 How to Learn from the Code

Each file is intentionally **verbose and pedagogical**.  
You’ll find inline explanations like:

```php
// Step 2: Build the ReAct loop
// The model "thinks", then decides which tool to use next.
```

Read them.  
Change things.  
Break things.  
That’s the Datapizza way of learning 🍕

---

## 🧠 Troubleshooting for Curious Minds

- “API key not found” → Check `.env` exists  
- “Connection timeout” → Your Pi fell asleep  
- “Out of memory” → Smaller model, smaller chunks  
- “Tool failed” → Read the tool’s code, understand its arguments  

Everything that breaks here, *teaches you something.*

---

## 📖 Further Reading

- `/datapizza/README.md` – full framework internals  
- [Prompting Guide](https://www.promptingguide.ai) – prompt design theory  
- [PHP Manual](https://www.php.net/docs.php) – syntax refresher  

---

## 🧃 Why It Matters

Because learning AI doesn’t require massive GPUs — just **curiosity** and a text editor.  
This project proves that **understanding beats horsepower**.  
If an LLM can reason on a 2011 Raspberry Pi, the future of accessible AI is already here.

---

## 🍕 Built with Curiosity

Made for those who want to *see the gears turn*.  
Every example is an x-ray of intelligence — small, transparent, and alive.

Run them.  
Read them.  
Modify them.  
Because the only real way to learn AI… is to build it yourself. 🚀
