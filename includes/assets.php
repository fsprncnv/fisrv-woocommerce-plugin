<?php

class Assets
{
    public static string $payment_method_card_css = '
        .f-card {
            display:flex; 
            flex-direction: column; 
            justify-content: center; 
            align-items: center; 
            height: 100px; 
            width: 200px;
            margin-right: 1.25em; 
            border: 1px solid #ccc; 
            border-radius: 8px;
            cursor: pointer;
        }
        .f-card:hover {
            background-color: #f5f5f5;
        }
        .f-card-selected {
            border-color: #38bdf8;
            background-color: #f0faff;
        }
    ';

    public static string $loader_css = '
        .lds-ellipsis,
        .lds-ellipsis div {
          box-sizing: border-box;
        }
        .lds-ellipsis {
          position: relative;
          width: 20px;
          height: 20px;
        }
        .hidden {
            display: none;
        }
        .show {
            display: inline-block;
        }
        .lds-ellipsis div {
          position: absolute;
          top: 20%;
          width: 50%;
          height: 50%;
          border-radius: 50%;
          background: currentColor;
          animation-timing-function: cubic-bezier(0, 1, 1, 0);
        }
        .lds-ellipsis div:nth-child(1) {
          left: 8px;
          animation: lds-ellipsis1 0.6s infinite;
        }
        .lds-ellipsis div:nth-child(2) {
          left: 8px;
          animation: lds-ellipsis2 0.6s infinite;
        }
        .lds-ellipsis div:nth-child(3) {
          left: 32px;
          animation: lds-ellipsis2 0.6s infinite;
        }
        .lds-ellipsis div:nth-child(4) {
          left: 56px;
          animation: lds-ellipsis3 0.6s infinite;
        }
        @keyframes lds-ellipsis1 {
          0% {
            transform: scale(0);
          }
          100% {
            transform: scale(1);
          }
        }
        @keyframes lds-ellipsis3 {
          0% {
            transform: scale(1);
          }
          100% {
            transform: scale(0);
          }
        }
        @keyframes lds-ellipsis2 {
          0% {
            transform: translate(0, 0);
          }
          100% {
            transform: translate(24px, 0);
          }
        }
        ';
}
