(function () {
  function firebaseErrorMessage(error) {
    if (typeof firebase === 'undefined' || window.firebaseConfigError) {
      return 'Firebase is not available or configured correctly. Please contact the site administrator.';
    }
    const code = error && error.code ? error.code : '';
    const messages = {
      'auth/email-already-in-use': 'An account with this email already exists. Sign in instead.',
      'auth/invalid-email': 'Enter a valid email address.',
      'auth/weak-password': 'Choose a password with at least 8 characters.',
      'auth/user-disabled': 'This account is disabled. Please contact support.',
      'auth/user-not-found': 'No Firebase account was found for this email.',
      'auth/wrong-password': 'The email or password is incorrect.',
      'auth/invalid-credential': 'The email or password is incorrect.',
      'auth/popup-closed-by-user': 'Google sign-in was cancelled.',
      'auth/popup-blocked': 'Your browser blocked the Google sign-in window. Allow popups and try again.',
      'auth/network-request-failed': 'A network error interrupted Firebase sign-in. Check your connection and try again.',
      'auth/operation-not-allowed': 'This sign-in method is not enabled in Firebase. Please contact the site administrator.',
      'auth/unauthorized-domain': 'This website domain is not authorized in Firebase. Please contact the site administrator.',
      'auth/account-exists-with-different-credential': 'An account with this email already exists using another sign-in method. Sign in with that method first.',
      'auth/too-many-requests': 'Too many requests were made. Wait a few minutes and try again.',
      'auth/invalid-api-key': 'Firebase is not configured correctly. Please contact the site administrator.'
    };
    return messages[code] || (error && error.message) || 'Authentication could not be completed. Please try again.';
  }

  async function postForm(url, values) {
    let response;
    try {
      response = await fetch(url, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'},
        body: new URLSearchParams(values)
      });
    } catch (error) {
      throw new Error('Could not reach the sign-in service. Check your connection and try again.');
    }
    let data;
    try {
      data = await response.json();
    } catch (error) {
      throw new Error('The sign-in service returned an invalid response. Please try again.');
    }
    if (!response.ok || !data.ok) {
      throw new Error(data.message || 'The sign-in service could not complete your request.');
    }
    return data;
  }

  window.customerAuth = {
    errorMessage: firebaseErrorMessage,
    sendPasswordReset: async function (email) {
      if (typeof firebase === 'undefined' || window.firebaseConfigError || !firebase.apps.length) {
        throw new Error('Firebase is not available or configured correctly. Please contact the site administrator.');
      }
      await firebase.auth().sendPasswordResetEmail(email);
    },
    prepareLegacyLink: function (csrf, email, password) {
      return postForm('backend/firebase-prepare-legacy.php', {csrf, email, password});
    },
    sync: async function (endpoint, csrf, user, details) {
      const idToken = await user.getIdToken(true);
      return postForm(endpoint, Object.assign({
        csrf,
        idToken,
        return_to: details.returnTo || 'home.php',
        first_name: details.firstName || '',
        last_name: details.lastName || ''
      }, details.extra || {}));
    }
  };
}());
