import React, { useState } from "react";
import "./Sidebar.css";
import logo from "../LogoBG.png";

const Sidebar = () => {
  const [isOpen, setIsOpen] = useState(true);

  return (
    <div className={`sidebar ${isOpen ? "open" : ""}`}>
      <div className="logo-details">
        <img src={logo} alt="Direct Edge Logo" width="50" height="50" />
        <div className="logo_name">Direct Edge</div>
        <i
          className="bx bx-menu"
          id="btn"
          onClick={() => setIsOpen(!isOpen)}
        ></i>
      </div>
      <ul className="nav-list">
        <li>
          <a href="#">
            <i className="bx bx-grid-alt"></i>
            <span className="links_name">Dashboard</span>
          </a>
          <span className="tooltip">Dashboard</span>
        </li>
        <li>
          <a href="#">
            <i className="bx bx-user"></i>
            <span className="links_name">Items</span>
          </a>
          <span className="tooltip">Items</span>
        </li>
        <li>
          <a href="#">
            <i className="bx bx-chat"></i>
            <span className="links_name">Restock goods</span>
          </a>
          <span className="tooltip">Restock goods</span>
        </li>
        <li>
          <a href="#">
            <i className="bx bx-pie-chart-alt-2"></i>
            <span className="links_name">Demand Forecast</span>
          </a>
          <span className="tooltip">Demand Forecast</span>
        </li>
        <li>
          <a href="#">
            <i className="bx bx-folder"></i>
            <span className="links_name">Sales Analytics</span>
          </a>
          <span className="tooltip">Sales Analytics</span>
        </li>
        <li>
          <a href="#">
            <i className="bx bx-cart-alt"></i>
            <span className="links_name">Bills</span>
          </a>
          <span className="tooltip">Bills</span>
        </li>
        <li>
          <a href="#">
            <i className="bx bx-heart"></i>
            <span className="links_name">Help Center</span>
          </a>
          <span className="tooltip">Help Center</span>
        </li>
        <li>
          <a href="#">
            <i className="bx bx-cog"></i>
            <span className="links_name">Setting</span>
          </a>
          <span className="tooltip">Setting</span>
        </li>
        <li className="profile">
          <div className="profile-details">
            <div className="name_job">
              <div className="name">Mayer Dowa Store</div>
              <div className="job">44/A South Basabo</div>
            </div>
          </div>
          <i className="bx bx-log-out" id="log_out"></i>
        </li>
      </ul>
    </div>
  );
};

export default Sidebar;
