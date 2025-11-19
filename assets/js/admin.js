(() => {
	const config = window.avaCfAdmin;

	if (!config) {
		return;
	}

	const button = document.getElementById('ava-cf-test-email');
	const result = document.getElementById('ava-cf-test-email-result');

	if (!button || !result) {
		return;
	}

	const setResult = (text, status) => {
		result.textContent = text || '';
		result.classList.remove('success', 'error', 'pending');

		if (status) {
			result.classList.add(status);
		}
	};

	button.addEventListener('click', () => {
		if (button.disabled) {
			return;
		}

		const messages = config.messages || {};

		setResult(messages.sending || '');
		button.disabled = true;

		const payload = new URLSearchParams();
		payload.append('action', 'ava_cf_test_email');
		payload.append('nonce', config.nonce || '');

		fetch(config.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
			},
			body: payload.toString(),
		})
			.then(async (response) => {
				const data = await response.json().catch(() => ({}));

				if (!response.ok || !data.success) {
					const message =
						(data && data.data && data.data.message) || messages.error || 'Request failed';
					throw new Error(message);
				}

				setResult(messages.success || 'OK', 'success');
			})
			.catch((error) => {
				setResult(error.message, 'error');
			})
			.finally(() => {
				button.disabled = false;
			});
	});
})();
