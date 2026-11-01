/*
 * Firebase Web SDK configuration.
 * Replace every placeholder with values from:
 * Firebase Console -> Project settings -> Your apps -> Web app.
 */
const firebaseConfig = {
  apiKey: "AIzaSyBOExkFVOxuitTwgQ-jRzDzsCtOFUw6Knc",
  authDomain: "beamers-legacy.firebaseapp.com",
  projectId: "beamers-legacy",
  storageBucket: "beamers-legacy.firebasestorage.app",
  messagingSenderId: "748639005252",
  appId: "1:748639005252:web:cc395ccfa8b01cfeebaa11"
};

if (typeof firebase !== "undefined") {
  try {
    if (!firebase.apps.length) {
      firebase.initializeApp(firebaseConfig);
    }
  } catch (error) {
    window.firebaseConfigError = error;
  }
} else {
  window.firebaseConfigError = new Error("Firebase SDK is unavailable.");
}
