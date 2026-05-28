const state = {
  projects: [],
  reviewItems: [],
  activeRole: "student",
};

const backendBase = "../backend";

function escapeHtml(value) {
  return String(value)
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&#39;");
}

function showToast(message, kind = "success") {
  const toast = document.getElementById("toast");
  if (!toast) {
    return;
  }

  toast.textContent = message;
  toast.classList.toggle("error", kind === "error");
  toast.classList.add("show");

  window.clearTimeout(showToast.timer);
  showToast.timer = window.setTimeout(() => {
    toast.classList.remove("show");
  }, 2400);
}

function extractProjectId(project) {
  if (!project) {
    return "";
  }

  return project.id || project._id || project.project_id || project.ObjectId || "";
}

function normalizeProject(project, index = 0) {
  const title = project.title || project.name || `Project ${index + 1}`;
  const description = project.description || project.summary || "No description provided yet.";
  const tags = Array.isArray(project.tags)
    ? project.tags
    : typeof project.tags === "string"
      ? project.tags.split(",").map((tag) => tag.trim()).filter(Boolean)
      : ["research", "approved"];
  const status = project.status || "approved";

  return {
    id: extractProjectId(project) || `project-${index + 1}`,
    title,
    description,
    tags,
    status,
    raw: project,
  };
}

function parseSearchResponse(rawText) {
  const matches = [];
  const regex = /\[(?:\d+)\]\s*=>\s*Array\s*\((.*?)\n\s*\)/gs;
  let block = regex.exec(rawText);

  if (!block) {
    const titleMatches = [...rawText.matchAll(/\[title\]\s*=>\s*(.+)/gi)];
    if (titleMatches.length) {
      const titles = titleMatches.map((entry) => entry[1].trim()).filter(Boolean);
      return titles.map((title, index) => ({ title, description: "Project details returned by PHP.", tags: ["approved"], _id: `parsed-${index}` }));
    }
    return [];
  }

  while (block) {
    const chunk = block[1];
    const title = (chunk.match(/\[title\]\s*=>\s*(.+)/i) || [])[1]?.trim() || "Untitled project";
    const description = (chunk.match(/\[description\]\s*=>\s*(.+)/i) || [])[1]?.trim() || "No description provided.";
    const tagsChunk = chunk.match(/\[tags\]\s*=>\s*Array\s*\((.*?)\n\s*\)/is);
    const tags = tagsChunk
      ? [...tagsChunk[1].matchAll(/=>\s*(.+)/g)].map((entry) => entry[1].trim()).filter(Boolean)
      : ["approved"];

    matches.push({ title, description, tags, _id: `parsed-${matches.length + 1}` });
    block = regex.exec(rawText);
  }

  return matches;
}

function renderTags(tags) {
  return tags.map((tag) => `<span class="tag">${escapeHtml(tag)}</span>`).join("");
}

function renderProjectCard(project, mode = "student") {
  const tags = renderTags(project.tags || []);
  const safeTitle = escapeHtml(project.title);
  const safeDescription = escapeHtml(project.description);
  const safeId = escapeHtml(project.id);

  return `
    <article class="project-card" data-project-id="${safeId}">
      <div class="project-meta">
        <span class="status-tag">${escapeHtml(project.status || "approved")}</span>
        <span class="project-id">${safeId}</span>
      </div>
      <h3 class="project-title">${safeTitle}</h3>
      <p class="project-description">${safeDescription}</p>
      <div class="tag-list">${tags}</div>
      <div class="card-actions">
        <button class="secondary-button join-button" type="button">Join</button>
        <button class="secondary-button outline-button review-approve ${mode === "faculty" ? "" : "hidden"}" type="button">Approve</button>
        <button class="secondary-button outline-button review-reject ${mode === "faculty" ? "" : "hidden"}" type="button">Reject</button>
      </div>
    </article>
  `;
}

function renderProjects(projects) {
  const grid = document.getElementById("projects-grid");
  if (!grid) {
    return;
  }

  if (!projects.length) {
    grid.innerHTML = `
      <article class="project-card">
        <h3 class="project-title">No results yet</h3>
        <p class="project-description">Search approved projects to load cards from search_projects.php.</p>
      </article>
    `;
    return;
  }

  grid.innerHTML = projects.map((project) => renderProjectCard(project, state.activeRole)).join("");
}

function renderReviewQueue(projects) {
  const queue = document.getElementById("review-queue");
  if (!queue) {
    return;
  }

  if (!projects.length) {
    queue.innerHTML = `
      <div class="review-item">
        <strong>No pending review items</strong>
        <p>Faculty actions will appear here once cards are loaded.</p>
      </div>
    `;
    return;
  }

  queue.innerHTML = projects.map((project) => `
    <article class="review-item" data-review-id="${escapeHtml(project.id)}">
      <strong>${escapeHtml(project.title)}</strong>
      <p>${escapeHtml(project.description)}</p>
      <div class="review-actions">
        <button class="secondary-button review-approve" type="button">Approve</button>
        <button class="secondary-button outline-button review-reject" type="button">Reject</button>
      </div>
    </article>
  `).join("");
}

function setStatus(message) {
  const status = document.getElementById("search-status");
  if (status) {
    status.textContent = message;
  }
}

async function runSearch(query) {
  setStatus(query ? `Searching for “${query}”` : "Loading approved projects...");

  const response = await fetch(`${backendBase}/search_projects.php?query=${encodeURIComponent(query)}`);
  const rawText = await response.text();

  let projects = [];
  try {
    projects = JSON.parse(rawText).map(normalizeProject);
  } catch (_error) {
    projects = parseSearchResponse(rawText).map(normalizeProject);
  }

  state.projects = projects;
  state.reviewItems = projects.slice(0, 4);
  renderProjects(projects);
  renderReviewQueue(state.reviewItems);
  setStatus(projects.length ? `${projects.length} project cards ready` : rawText.trim() || "No matching projects found");
}

async function joinProject(projectCard) {
  const projectId = projectCard.dataset.projectId;
  const body = new URLSearchParams({ project_id: projectId });

  const response = await fetch(`${backendBase}/join_project.php`, {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
    },
    body,
  });

  const message = await response.text();
  if (response.ok) {
    showToast(message || "Joined project", "success");
    return;
  }

  showToast(message || "Unable to join project", "error");
}

async function reviewProject(projectId, action, card) {
  const body = new URLSearchParams({ project_id: projectId, action });

  const response = await fetch(`${backendBase}/review_project.php`, {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
    },
    body,
  });

  const message = await response.text();
  if (response.ok) {
    showToast(message || `Project ${action}d`, "success");
    if (card) {
      card.remove();
    }
    return;
  }

  showToast(message || "Review action failed", "error");
}

function applyRole(role) {
  state.activeRole = role;

  document.querySelectorAll(".role-switch").forEach((button) => {
    button.classList.toggle("active", button.dataset.role === role);
  });

  document.querySelectorAll(".student-panel, .faculty-panel").forEach((panel) => {
    panel.classList.remove("hidden");
  });

  if (role === "student") {
    document.querySelectorAll(".faculty-panel .review-approve, .faculty-panel .review-reject").forEach((button) => {
      button.classList.remove("hidden");
    });
  }

  renderProjects(state.projects);
}

function wireLoginTabs() {
  const buttons = document.querySelectorAll("[data-tab-target]");
  const panes = document.querySelectorAll(".form-pane");

  buttons.forEach((button) => {
    button.addEventListener("click", () => {
      const targetId = button.dataset.tabTarget;
      buttons.forEach((tab) => tab.classList.toggle("active", tab === button));
      panes.forEach((pane) => pane.classList.toggle("active", pane.id === targetId));
    });
  });
}

function wireDashboard() {
  const searchForm = document.getElementById("search-form");
  const searchInput = document.getElementById("search-input");

  if (!searchForm) {
    return;
  }

  searchForm.addEventListener("submit", async (event) => {
    event.preventDefault();
    await runSearch(searchInput.value.trim());
  });

  document.querySelectorAll(".role-switch").forEach((button) => {
    button.addEventListener("click", () => applyRole(button.dataset.role));
  });

  document.addEventListener("click", async (event) => {
    const joinButton = event.target.closest(".join-button");
    if (joinButton) {
      const card = joinButton.closest(".project-card");
      if (card) {
        await joinProject(card);
      }
      return;
    }

    const approveButton = event.target.closest(".review-approve");
    if (approveButton) {
      const card = approveButton.closest(".project-card, .review-item");
      const projectId = card?.dataset.projectId || card?.dataset.reviewId;
      if (projectId) {
        await reviewProject(projectId, "approved", card);
      }
      return;
    }

    const rejectButton = event.target.closest(".review-reject");
    if (rejectButton) {
      const card = rejectButton.closest(".project-card, .review-item");
      const projectId = card?.dataset.projectId || card?.dataset.reviewId;
      if (projectId) {
        await reviewProject(projectId, "rejected", card);
      }
    }
  });

  const seedProjects = [
    {
      _id: "66c1a1f8e3c4d7a812345678",
      title: "Smart classroom air monitoring",
      description: "Track heat, humidity, and ventilation changes with a low-cost dashboard.",
      tags: ["IoT", "sensors", "student"],
      status: "approved",
    },
    {
      _id: "66c1a1f8e3c4d7a812345679",
      title: "Waste-aware campus logistics",
      description: "Improve collection routing and visibility for sustainability operations.",
      tags: ["logistics", "green-tech"],
      status: "approved",
    },
    {
      _id: "66c1a1f8e3c4d7a812345680",
      title: "Mentored lab collaboration board",
      description: "Faculty-moderated spaces for proposals, reviews, and team formation.",
      tags: ["faculty", "review", "collaboration"],
      status: "pending",
    },
  ].map(normalizeProject);

  state.projects = seedProjects;
  state.reviewItems = seedProjects.filter((project) => project.status !== "approved");
  renderProjects(seedProjects);
  renderReviewQueue(state.reviewItems);
  applyRole("student");
  runSearch("").catch(() => {
    setStatus("Backend search endpoint is reachable, but the response is not structured yet.");
  });
}

if (document.querySelector(".auth-layout")) {
  wireLoginTabs();
}

if (document.body?.dataset.page === "dashboard") {
  wireDashboard();
}
