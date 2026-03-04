#!/bin/bash

# Zabbix Agent/Agent2 Install Script (PSK-free)
#
# Usage:
#   bash install-zabbix.sh --server-host '10.90.27.190' --hostname 'myserver'
#   bash install-zabbix.sh --server-host '10.90.27.190'               # hostname = system default
#   bash install-zabbix.sh --agent --server-host '10.90.27.190'       # agentd instead of agent2
#   bash install-zabbix.sh --uninstall
#   bash install-zabbix.sh --configure --server-host '10.90.27.190'   # only update config
#
# One-liner:
#   $(command -v curl || echo "wget -O -") https://yourserver/install-zabbix.sh | bash -s -- --server-host '10.90.27.190' --hostname 'test'

function show_help
{
    echo "Usage:"
    echo "    $(basename ${BASH_SOURCE[0]}) (--install|--reinstall) [--agent|--agent2] [--version X.Y] [<configuration options>]"
    echo "    $(basename ${BASH_SOURCE[0]}) --configure [--agent|--agent2] [<configuration options>]"
    echo "    $(basename ${BASH_SOURCE[0]}) --uninstall"
    echo ""
    echo "Modes:"
    echo "    --install    Install Zabbix agent and configure it   (default)"
    echo "    --reinstall  Uninstall then install"
    echo "    --configure  Only update config file (agent already installed)"
    echo "    --uninstall  Remove Zabbix from system"
    echo ""
    echo "Options:"
    echo "    --agent | --agent2        Which agent to install (default: agent2)"
    echo "    --version X.Y             Zabbix version (default: $default_zabbix_version)"
    echo "    --server-host <ip/host>   Zabbix server/proxy address"
    echo "    --server-host-stdin       Read server host from stdin"
    echo "    --hostname <str>          Hostname shown in Zabbix"
    echo "    --hostname-stdin          Read hostname from stdin"
    echo "    --repo-url <URL>          Override repo URL (default: $default_repo_url)"
}

function run
{
    local mode=''
    local default_zabbix_version=7.4
    local zabbix_version=$default_zabbix_version
    local components=''
    local server_host=''
    local server_host_stdin=false
    local hostname=''
    local hostname_stdin=false
    local default_repo_url=https://repo.zabbix.com/zabbix
    local repo_url=$default_repo_url

    while [[ $# -gt 0 ]]; do case $1 in
        --install)        mode=install ;;
        --uninstall)      mode=uninstall ;;
        --reinstall)      mode=reinstall ;;
        --configure)      mode=configure ;;

        -v|--version|--zabbix-version)
            [[ -n "$2" ]] || die "option $1 requires a parameter"
            zabbix_version=$2; shift ;;

        --agent)          components+=,agentd ;;
        --no-agent)       components=${components//,agentd/} ;;
        --agent2)         components+=,agent2 ;;
        --no-agent2)      components=${components//,agent2/} ;;

        --server-host)
            [[ -n "$2" ]] || die "option $1 requires a parameter"
            server_host=$2; shift
            server_host_stdin=false ;;
        --server-host-stdin)
            server_host_stdin=true ;;

        --hostname)
            [[ -n "$2" ]] || die "option $1 requires a parameter"
            hostname=$2; shift
            hostname_stdin=false ;;
        --hostname-stdin)
            hostname_stdin=true ;;

        --repo-url)
            [[ -n "$2" ]] || die "option $1 requires a parameter"
            repo_url="$2"; shift ;;

        -x|--bashtrace)   set -x ;;
        -h|--help|help)   show_help; exit 0 ;;
        --)               shift; break ;;
        -*)               die "unsupported option $1" ;;
    esac; shift; done

    # ── Root check ────────────────────────────────────────────────────────────
    [[ "$(id -u)" == 0 ]] || die "this script must be run as root"

    # ── Detect OS ─────────────────────────────────────────────────────────────
    [[ -f /etc/os-release ]] || die "cannot find /etc/os-release file"

    [[ "$(cat /etc/os-release)" =~ [[:space:]]ID=\"?([a-zA-Z0-9-]+)\"?[[:space:]] ]] ||
        die "cannot extract OS name from /etc/os-release file"
    local os_name=${BASH_REMATCH[1]}

    [[ "$(cat /etc/os-release)" =~ [[:space:]]VERSION_ID=\"?(([0-9]+)(\.[0-9]+)?)\"?[[:space:]] ]] ||
        die "cannot extract OS version from /etc/os-release file"
    local os_version=${BASH_REMATCH[1]}
    local os_version_x=${BASH_REMATCH[2]}

    local deb_os_name_suffix=""
    local install_type=""
    local repo_os_name=""

    case $os_name in
        almalinux)     install_type=rhel; repo_os_name=alma ;;
        amzn)          install_type=rhel; repo_os_name=amazonlinux ;;
        centos)        install_type=rhel; repo_os_name=centos ;;
        ol)            install_type=rhel; repo_os_name=oracle ;;
        rhel)          install_type=rhel; repo_os_name=rhel ;;
        rocky)         install_type=rhel; repo_os_name=rocky ;;
        opensuse-leap) install_type=sles; repo_os_name=sles ;;
        sles)          install_type=sles; repo_os_name=sles ;;
        debian)
            deb_os_name_suffix=debian; install_type=deb
            [[ -f /etc/apt/sources.list.d/raspi.list ]] && repo_os_name=raspbian || repo_os_name=debian ;;
        raspbian)      deb_os_name_suffix=debian; install_type=deb; repo_os_name=raspbian ;;
        ubuntu)        deb_os_name_suffix=ubuntu; install_type=deb; repo_os_name=ubuntu ;;
        *)             die "unsupported OS: $os_name" ;;
    esac

    echo "► OS: $os_name $os_version ($install_type)"

    # ── OS-specific functions ─────────────────────────────────────────────────
    if [[ $install_type == deb ]]; then
        function install_packages_low_level { dpkg -i "$@"; }
        function install_packages           { apt -y install "$@"; }
        function uninstall_packages         { apt -y purge "$@"; }
        function update_repo                { apt update; }
        function zabbix_release_package_name \
            { echo zabbix-release_latest_${zabbix_version}+${deb_os_name_suffix}${os_version}_all.deb; }
        function zabbix_release_repo_dir \
            { echo ${repo_url_with_version_and_os}/pool/main/z/zabbix-release; }
    else
        if [[ $install_type == rhel ]]; then
            if [[ $os_version_x -ge 8 ]]; then
                function yum_or_dnf { dnf "$@"; }
            else
                function yum_or_dnf { yum "$@"; }
            fi
            function install_packages_low_level { rpm -Uvh "$@"; }
            function install_packages           { yum_or_dnf -y install "$@"; }
            function uninstall_packages         { yum_or_dnf -y remove "$@"; }
            function update_repo                { yum_or_dnf clean all; }
            function zabbix_release_package_name \
                { echo zabbix-release-latest-${zabbix_version}.el${os_version_x}.noarch.rpm; }
        elif [[ $install_type == sles ]]; then
            function install_packages_low_level { rpm -Uvh "$@"; }
            function install_packages           { zypper install -y "$@"; }
            function uninstall_packages         { zypper remove -y "$@"; }
            function update_repo                { zypper --gpg-auto-import-keys refresh "$@"; }
            function zabbix_release_package_name \
                { echo zabbix-release-latest-${zabbix_version}.sles${os_version_x}.noarch.rpm; }
        fi
        function zabbix_release_repo_dir
        {
            local url=${repo_url_with_version_and_os}/${os_version_x}
            if [[ $zabbix_version_x -ge 8 ]] || [[ $zabbix_version_x -eq 7 && $zabbix_version_y -ge 2 ]]; then
                url+=/noarch
            else
                url+=/x86_64
            fi
            echo $url
        }
    fi

    # ── Mode default ──────────────────────────────────────────────────────────
    [[ -z "$mode" ]] && mode=install

    # ── Download helper & version parse ──────────────────────────────────────
    if [[ "${mode}" =~ ^(install|reinstall)$ ]]; then
        if command -v curl > /dev/null; then
            function download_file { curl -O --remote-name "$@"; }
        elif command -v wget > /dev/null; then
            function download_file { wget "$@"; }
        else
            die "install curl or wget"
        fi

        [[ "$zabbix_version" =~ ^([0-9]+)\.([0-9]+)$ ]] ||
            die "cannot parse zabbix version \"$zabbix_version\""
        local zabbix_version_x=${BASH_REMATCH[1]}
        local zabbix_version_y=${BASH_REMATCH[2]}

        local repo_url_with_version=${repo_url}/${zabbix_version}
        if [[ $zabbix_version_x -ge 8 ]] || [[ $zabbix_version_x -eq 7 && $zabbix_version_y -ge 2 ]]; then
            repo_url_with_version+=/release
        fi

        if [[ $install_type == rhel && $zabbix_version_x -lt 7 ]]; then
            local repo_url_with_version_and_os=${repo_url_with_version}/rhel
        else
            local repo_url_with_version_and_os=${repo_url_with_version}/${repo_os_name}
        fi
    fi

    # ── Agent selection ───────────────────────────────────────────────────────
    if [[ "${mode}" =~ ^(install|reinstall|configure)$ ]]; then
        if [[ "${components}," =~ ,agentd, && "${components}," =~ ,agent2, ]]; then
            die "both --agent and --agent2 cannot be selected at the same time"
        fi

        [[ -z "$components" ]] && components=,agent2

        if [[ "${components}," =~ ,agentd, ]]; then
            local packages_to_install="zabbix-agent"
            local agent_binary=zabbix_agentd
            local agent_conf_file=/etc/zabbix/zabbix_agentd.conf
            local service=zabbix-agent
        elif [[ "${components}," =~ ,agent2, ]]; then
            local packages_to_install="zabbix-agent2"
            local agent_binary=zabbix_agent2
            local agent_conf_file=/etc/zabbix/zabbix_agent2.conf
            local service=zabbix-agent2
        elif [[ ${mode} == configure ]]; then
            if [[ -f /etc/zabbix/zabbix_agentd.conf ]]; then
                local service=zabbix-agent
                local agent_conf_file=/etc/zabbix/zabbix_agentd.conf
            elif [[ -f /etc/zabbix/zabbix_agent2.conf ]]; then
                local service=zabbix-agent2
                local agent_conf_file=/etc/zabbix/zabbix_agent2.conf
            else
                die "cannot find configuration file to process"
            fi
        fi

        $server_host_stdin && { echo -n "enter Zabbix server/proxy IP or hostname: "; read server_host; }
        $hostname_stdin    && { echo -n "enter hostname: "; read hostname; }
    fi

    # ── Uninstall ─────────────────────────────────────────────────────────────
    if [[ ${mode} =~ ^(uninstall|reinstall)$ ]]; then
        echo "► Removing Zabbix..."
        uninstall_packages "*zabbix*" || die
    fi

    # ── Install ───────────────────────────────────────────────────────────────
    if [[ "${mode}" =~ ^(install|reinstall)$ ]]; then
        local zabbix_release_url=$(zabbix_release_repo_dir)/$(zabbix_release_package_name)
        local dir=$(mktemp -d /tmp/install-zabbix-${zabbix_version}-$(date +%Y%m%d-%H%M%S)-XXXX)

        echo "► Downloading: $zabbix_release_url"
        (
            cd $dir || die
            download_file $zabbix_release_url ||
                die "cannot download zabbix-release package from $zabbix_release_url"
            install_packages_low_level ./$(zabbix_release_package_name) ||
                die "cannot install zabbix-release package"
            update_repo 'Zabbix Official Repository' ||
                die "cannot update Zabbix repository"
            echo "► Installing $packages_to_install..."
            install_packages $packages_to_install ||
                die "cannot install zabbix packages"
            $agent_binary -t agent.version ||
                die "$agent_binary seems non-functional"
        ) || die
        rm -rf $dir
    fi

    # ── Configure ─────────────────────────────────────────────────────────────
    if [[ ${mode} =~ ^(install|reinstall|configure)$ ]]; then
        if [[ -n "$server_host" ]]; then
            local server_passive=''
            local server_active=''
            local s; for s in ${server_host//','/' '}; do
                if [[ "$s" =~ ^([0-9]+\.[0-9]+\.[0-9]+\.[0-9])(:[0-9]+)?$ ]]; then
                    server_passive+=${BASH_REMATCH[1]},
                    server_active+=${BASH_REMATCH[1]}${BASH_REMATCH[2]},
                elif [[ "$s" =~ ^\[(.+)\](:[0-9]+)?$ ]]; then
                    server_passive+=${BASH_REMATCH[1]},
                    server_active+=[${BASH_REMATCH[1]}]${BASH_REMATCH[2]},
                elif [[ "$s" =~ :[0-9A-Fa-f]*: ]]; then
                    server_passive+=$s,
                    server_active+=$s,
                else
                    server_passive+=${s%:*},
                    server_active+=$s,
                fi
            done
            setup_conf_option $agent_conf_file Server       "${server_passive%,}"
            setup_conf_option $agent_conf_file ServerActive "${server_active%,}"
        fi

        [[ -n "$hostname" ]] && setup_conf_option $agent_conf_file Hostname "$hostname"

        echo "► Starting $service..."
        systemctl restart $service && systemctl enable $service || die
        systemctl status $service --no-pager

        echo ""
        echo "✓ Done!"
        echo "  Agent  : $agent_binary"
        echo "  Server : $server_host"
        echo "  Host   : ${hostname:-$(hostname -s)}"
        echo "  Config : $agent_conf_file"
    fi
}

function setup_conf_option
{
    local file=$1
    local option=$2
    local value=$3

    if grep -q "^${option}=${value}$" $file; then
        :
    elif grep -q "^${option}=" $file; then
        sed -i "s/^${option}=.*$/${option}=${value}/g" $file || die
    else
        [[ $(grep -ne "#[[:space:]]$option" $file) =~ ^([0-9]+): ]] ||
            die "cannot find $option option in $file file"
        local line=${BASH_REMATCH[1]}
        sed -i "$(( $line + 1 ))a ${option}=${value}\n" $file || die
    fi
}

function die
{
    [[ -n "$@" ]] && >&2 echo -e "$@"
    exit 1
}

run "$@"
