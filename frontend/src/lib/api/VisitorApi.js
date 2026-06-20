export const logVisitor = async (deviceId, locationData = {}) => {
  return await fetch(`${import.meta.env.VITE_APP_PATH}/visitors`, {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      Accept: "application/json",
    },
    body: JSON.stringify({ device_id: deviceId, ...locationData }),
  });
};

export const getVisitorCount = async () => {
  const token = localStorage.getItem("token");
  return await fetch(`${import.meta.env.VITE_APP_PATH}/admin/visitors/count`, {
    method: "GET",
    headers: {
      Authorization: `Bearer ${token}`,
      Accept: "application/json",
    },
  });
};

export const adminGetVisitors = async (page = 1) => {
  const token = localStorage.getItem("token");
  return await fetch(`${import.meta.env.VITE_APP_PATH}/admin/visitors?page=${page}`, {
    method: "GET",
    headers: {
      Authorization: `Bearer ${token}`,
      Accept: "application/json",
    },
  });
};

export const adminDeleteVisitor = async (id) => {
  const token = localStorage.getItem("token");
  return await fetch(`${import.meta.env.VITE_APP_PATH}/admin/visitors/${id}`, {
    method: "DELETE",
    headers: {
      Authorization: `Bearer ${token}`,
      Accept: "application/json",
    },
  });
};

export const adminClearAllVisitors = async () => {
  const token = localStorage.getItem("token");
  return await fetch(`${import.meta.env.VITE_APP_PATH}/admin/visitors`, {
    method: "DELETE",
    headers: {
      Authorization: `Bearer ${token}`,
      Accept: "application/json",
    },
  });
};
