document.addEventListener('DOMContentLoaded', () => {
	const forms = document.querySelectorAll('.ava-contact-form__form');
	const requiredMessage =
		(window.avaContactForm && window.avaContactForm.i18n && window.avaContactForm.i18n.required) ||
		'Veuillez remplir tous les champs obligatoires.';

	const clearFieldError = (wrapper) => {
		if (!wrapper) {
			return;
		}
		wrapper.classList.remove('has-error');
		wrapper.querySelectorAll('.is-invalid').forEach((el) => el.classList.remove('is-invalid'));
	};

	const updateOptionState = (input) => {
		if (!input) {
			return;
		}

		const option = input.closest('.ava-contact-form__option');

		if (!option) {
			return;
		}

		if (input.type === 'checkbox') {
			option.classList.toggle('is-selected', input.checked);
			return;
		}

		if (input.type === 'radio') {
			const name = input.name;
			if (name) {
				document
					.querySelectorAll(`input[type="radio"][name="${CSS.escape(name)}"]`)
					.forEach((radio) => {
						const radioOption = radio.closest('.ava-contact-form__option');
						if (radioOption) {
							radioOption.classList.toggle('is-selected', radio.checked);
						}
					});
			} else {
				option.classList.toggle('is-selected', input.checked);
			}
		}
	};

	const markFieldInvalid = (wrapper, elements) => {
		if (!wrapper) {
			return;
		}
		wrapper.classList.add('has-error');
		(elements || wrapper.querySelectorAll('input, textarea, select')).forEach((el) => el.classList.add('is-invalid'));
	};

	const validateWrapper = (wrapper) => {
		if (!wrapper) {
			return true;
		}

		const required = wrapper.dataset.required === 'true';

		if (!required) {
			return true;
		}

		const type = wrapper.dataset.fieldType || '';
		clearFieldError(wrapper);

		switch (type) {
			case 'checkbox': {
				const checkboxes = Array.from(wrapper.querySelectorAll('input[type="checkbox"]'));
				const hasValue = checkboxes.some((input) => input.checked);
				if (!hasValue) {
					markFieldInvalid(wrapper, checkboxes);
				}
				return hasValue;
			}
			case 'radio': {
				const radios = Array.from(wrapper.querySelectorAll('input[type="radio"]'));
				const hasValue = radios.some((input) => input.checked);
				if (!hasValue) {
					markFieldInvalid(wrapper, radios);
				}
				return hasValue;
			}
			case 'multiselect': {
				const select = wrapper.querySelector('select');
				const hasValue = !!select && Array.from(select.selectedOptions).length > 0;
				if (!hasValue && select) {
					markFieldInvalid(wrapper, [select]);
				}
				return hasValue;
			}
			case 'select': {
				const select = wrapper.querySelector('select');
				const hasValue = !!select && select.value.trim() !== '';
				if (!hasValue && select) {
					markFieldInvalid(wrapper, [select]);
				}
				return hasValue;
			}
			default: {
				const control = wrapper.querySelector('input, textarea, select');
				if (!control) {
					return true;
				}
				const hasValue = control.value.trim() !== '';
				if (!hasValue) {
					markFieldInvalid(wrapper, [control]);
				}
				return hasValue;
			}
		}
	};

	forms.forEach((form) => {
		const container = form.closest('.ava-contact-form');

		if (container && container.dataset.mode === 'multi') {
			return;
		}

		const clearHandler = (event) => {
			const target = event.target;
			if (!target || !target.classList) {
				return;
			}
			if (target.type === 'checkbox' || target.type === 'radio') {
				updateOptionState(target);
			}
			const wrapper = target.closest('.ava-contact-form__field');
			if (!wrapper) {
				return;
			}

			if (target.type === 'checkbox' || target.type === 'radio') {
				if (target.checked) {
					clearFieldError(wrapper);
				}
			} else if (target.value.trim()) {
				clearFieldError(wrapper);
			}
		};

		form.addEventListener('input', clearHandler);
		form.addEventListener('change', clearHandler);
		form
			.querySelectorAll('.ava-contact-form__option input[type="checkbox"], .ava-contact-form__option input[type="radio"]')
			.forEach((input) => {
				updateOptionState(input);
				if (!input.dataset.avaOptionBound) {
					input.dataset.avaOptionBound = '1';
					input.addEventListener('change', () => updateOptionState(input));
				}
			});

		form.addEventListener('submit', (event) => {
			const wrappers = Array.from(form.querySelectorAll('.ava-contact-form__field'));
			let isValid = true;
			let firstInvalid = null;

			wrappers.forEach((wrapper) => {
				if (!validateWrapper(wrapper)) {
					isValid = false;
					if (!firstInvalid) {
						firstInvalid = wrapper.querySelector('input, textarea, select');
					}
				}
			});

			if (!isValid) {
				event.preventDefault();
				alert(requiredMessage);
				if (firstInvalid && typeof firstInvalid.focus === 'function') {
					firstInvalid.focus({ preventScroll: true });
				}
			}
		});
	});
});

