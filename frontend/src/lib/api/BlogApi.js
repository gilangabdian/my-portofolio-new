const getAuthHeaders = () => {
  const token = localStorage.getItem("token");
  return {
    Authorization: `Bearer ${token}`,
    Accept: "application/json",
  };
};

export const getAllBlogsPublic = async () => {
  return await fetch(`${import.meta.env.VITE_APP_PATH}/blogs`, {
    method: "GET",
    headers: {
      Accept: "application/json",
    },
  });
};

export const getSingleBlogPublic = async (slug) => {
  return await fetch(`${import.meta.env.VITE_APP_PATH}/blogs/${slug}`, {
    method: "GET",
    headers: {
      Accept: "application/json",
    },
  });
};

export const getAllBlogsAdmin = async () => {
  return await fetch(`${import.meta.env.VITE_APP_PATH}/admin/blogs`, {
    method: "GET",
    headers: getAuthHeaders(),
  });
};

export const getSingleBlogAdmin = async (id) => {
  return await fetch(`${import.meta.env.VITE_APP_PATH}/admin/blogs/${id}`, {
    method: "GET",
    headers: getAuthHeaders(),
  });
};

export const createBlog = async (data) => {
  return await fetch(`${import.meta.env.VITE_APP_PATH}/blogs`, {
    method: "POST",
    headers: {
      ...getAuthHeaders(),
      "Content-Type": "application/json",
    },
    body: JSON.stringify(data),
  });
};

export const updateBlog = async (id, data) => {
  return await fetch(`${import.meta.env.VITE_APP_PATH}/blogs/${id}`, {
    method: "PUT",
    headers: {
      ...getAuthHeaders(),
      "Content-Type": "application/json",
    },
    body: JSON.stringify(data),
  });
};

export const deleteBlog = async (id) => {
  return await fetch(`${import.meta.env.VITE_APP_PATH}/blogs/${id}`, {
    method: "DELETE",
    headers: getAuthHeaders(),
  });
};

export const uploadBlogImage = async (formData) => {
  return await fetch(`${import.meta.env.VITE_APP_PATH}/blogs/upload-image`, {
    method: "POST",
    headers: getAuthHeaders(), // Jangan tambah Content-Type khusus, biarkan browser set multipart/form-data boundary
    body: formData,
  });
};
