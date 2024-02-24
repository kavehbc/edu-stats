build:
	python3 -m pip install --upgrade build
	python3 -m build

push:
	python3 -m pip install --upgrade twine
	python3 -m twine upload dist/*

install:
	pip install -e . --upgrade --upgrade-strategy only-if-needed

install-whl:
    pip install dist/edustats-0.1.0-py3-none-any.whl

module:
	pip install dist/edustats-0.1.0.tar.gz
