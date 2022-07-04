FROM alpine

RUN apk update && apk add cargo && cargo install lsd --root /bin

RUN apk add \
    bat \
    composer \
    curl \
    docker \
    docker-compose \
    git \
    github-cli \
    ipython \
    micro \
    neofetch \
    neovim \
    nerd-fonts \
    perl \
    shadow \
    ssh \
    zsh \
    zsh-theme-powerlevel10k \
    zsh-vcs

# add non-root user
ARG USERNAME=terry_davis
ARG USER_UID=1000
ARG USER_GID=$USER_UID

RUN groupadd --gid $USER_GID $USERNAME \
    && useradd --uid $USER_UID --gid $USER_GID -m $USERNAME -s /bin/zsh

# zsh theme
COPY .devcontainer/.config /home/$USERNAME/.config
RUN mkdir -p /home/$USERNAME/.local/share/zsh/plugins
RUN ln -s /usr/share/zsh/plugins/powerlevel10k /home/$USERNAME/.local/share/zsh/plugins/
RUN chown -R $USERNAME:$USER_GID /home/$USERNAME

USER $USERNAME

WORKDIR "/workspace"

CMD ["/bin/ash"]
