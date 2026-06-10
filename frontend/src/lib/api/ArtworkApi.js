const getAuthHeaders = () => {
  const token = localStorage.getItem("token");
  return {
    Authorization: `Bearer ${token}`,
    Accept: "application/json",
  };
};

export const getAllArtworks = async () => {
  return await fetch(`${import.meta.env.VITE_APP_PATH}/artworks`, {
    method: "GET",
    headers: {
      Accept: "application/json",
    },
  });
};

export const storeArtwork = async (formData) => {
  return await fetch(`${import.meta.env.VITE_APP_PATH}/artworks`, {
    method: "POST",
    headers: getAuthHeaders(),
    body: formData,
  });
};

export const deleteArtwork = async (id) => {
  return await fetch(`${import.meta.env.VITE_APP_PATH}/artworks/${id}`, {
    method: "DELETE",
    headers: getAuthHeaders(),
  });
};
